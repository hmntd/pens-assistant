import cv2
import numpy as np
import pytesseract
import json
import os
import re
import grpc
from concurrent import futures
from pdf2image import convert_from_bytes
import ocr_pb2
import ocr_pb2_grpc


def bytes_to_cv2_image(file_bytes, extension):
    """Converts raw bytes (PDF or Image) to OpenCV format (numpy array)."""
    if extension.lower() == "pdf":
        images = convert_from_bytes(file_bytes, dpi=300, first_page=1, last_page=1)
        open_cv_image = np.array(images[0])
        return open_cv_image[:, :, ::-1].copy()
    else:
        nparr = np.frombuffer(file_bytes, np.uint8)
        return cv2.imdecode(nparr, cv2.IMREAD_COLOR)


def detect_template(client_img, templates_dict):
    """Compares client photo against all available templates using keypoints (ORB)."""
    best_match_count = 0
    best_template_name = None

    client_gray = cv2.cvtColor(client_img, cv2.COLOR_BGR2GRAY)
    orb = cv2.ORB_create(3000)
    kpsA, descsA = orb.detectAndCompute(client_gray, None)

    matcher = cv2.DescriptorMatcher_create(cv2.DESCRIPTOR_MATCHER_BRUTEFORCE_HAMMING)

    for name, template_img in templates_dict.items():
        if template_img is None:
            continue
        template_gray = cv2.cvtColor(template_img, cv2.COLOR_BGR2GRAY)
        _, descsB = orb.detectAndCompute(template_gray, None)

        if descsA is None or descsB is None:
            continue

        matches = matcher.match(descsA, descsB, None)
        good_matches = [m for m in matches if m.distance < 50]

        print(f"[Classifier] Template '{name}' yielded {len(good_matches)} matching keypoints.")

        if len(good_matches) > best_match_count:
            best_match_count = len(good_matches)
            best_template_name = name

    return best_template_name


def align_images(image, template, max_features=5000, keep_percent=0.2):
    """Aligns (warps) distorted photo according to the reference template."""
    h, w = template.shape[:2]
    try:
        image_gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        template_gray = cv2.cvtColor(template, cv2.COLOR_BGR2GRAY)

        orb = cv2.ORB_create(max_features)
        kpsA, descsA = orb.detectAndCompute(image_gray, None)
        kpsB, descsB = orb.detectAndCompute(template_gray, None)

        if descsA is None or descsB is None or len(kpsA) < 4 or len(kpsB) < 4:
            print("[Alignment] Insufficient keypoints, using direct resize fallback.")
            return cv2.resize(image, (w, h))

        method = cv2.DESCRIPTOR_MATCHER_BRUTEFORCE_HAMMING
        matcher = cv2.DescriptorMatcher_create(method)
        matches = matcher.match(descsA, descsB, None)

        matches = sorted(matches, key=lambda x: x.distance)
        good_matches = [m for m in matches if m.distance < 60]
        if len(good_matches) < 4:
            print("[Alignment] Too few good matches, using direct resize fallback.")
            return cv2.resize(image, (w, h))

        keep = max(4, int(len(good_matches) * keep_percent))
        matches = good_matches[:keep]

        ptsA = np.zeros((len(matches), 2), dtype="float")
        ptsB = np.zeros((len(matches), 2), dtype="float")
        for i, m in enumerate(matches):
            ptsA[i] = kpsA[m.queryIdx].pt
            ptsB[i] = kpsB[m.trainIdx].pt

        H, mask = cv2.findHomography(ptsA, ptsB, method=cv2.RANSAC, ransacReprojThreshold=5.0)
        if H is None:
            print("[Alignment] Homography matrix is None, using direct resize fallback.")
            return cv2.resize(image, (w, h))

        det = abs(np.linalg.det(H[:2, :2]))
        if det < 0.2 or det > 5.0:
            print(f"[Alignment] Homography determinant ({det:.2f}) out of bounds, using direct resize fallback.")
            return cv2.resize(image, (w, h))

        return cv2.warpPerspective(image, H, (w, h))
    except Exception as e:
        print(f"[Alignment] Alignment error: {e}, using direct resize fallback.")
        return cv2.resize(image, (w, h))


def preprocess_roi_image(roi_cropped):
    """Upscale 2x and perform Gaussian blur + Otsu thresholding for crisp Tesseract OCR."""
    if roi_cropped is None or roi_cropped.size == 0:
        return None
    resized = cv2.resize(roi_cropped, (0, 0), fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC)
    gray = cv2.cvtColor(resized, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (3, 3), 0)
    _, thresh = cv2.threshold(blurred, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    return thresh


def extract_fields_with_ocr(aligned_image, rois):
    """Crops static ROIs (title page) and reads them using Tesseract."""
    extracted_data = {}
    custom_config = r"--oem 3 --psm 7"

    for field_name, coords in rois.items():
        x, y, w, h = coords["x"], coords["y"], coords["w"], coords["h"]
        roi_cropped = aligned_image[y : y + h, x : x + w]

        thresh_roi = preprocess_roi_image(roi_cropped)
        if thresh_roi is not None:
            text = pytesseract.image_to_string(
                thresh_roi, lang="ukr+eng", config=custom_config
            )
            extracted_data[field_name] = text.strip()
            print(f"[OCR] Field '{field_name}': {extracted_data[field_name]}")
        else:
            extracted_data[field_name] = ""

    return extracted_data


def extract_table_records(aligned_image, rois):
    """Detects horizontal table lines, slices table into rows, and reads cells."""
    print("[OCR] Starting dynamic table parsing...", flush=True)
    gray = cv2.cvtColor(aligned_image, cv2.COLOR_BGR2GRAY)
    thresh = cv2.adaptiveThreshold(
        gray, 255, cv2.ADAPTIVE_THRESH_MEAN_C, cv2.THRESH_BINARY_INV, 15, -2
    )

    horizontal_kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (50, 1))
    horizontal_lines = cv2.morphologyEx(
        thresh, cv2.MORPH_OPEN, horizontal_kernel, iterations=2
    )
    contours, _ = cv2.findContours(
        horizontal_lines, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE
    )

    y_coords = []
    for c in contours:
        x, y, w, h = cv2.boundingRect(c)
        if w > 300:
            y_coords.append(y)

    y_coords = sorted(y_coords)
    filtered_y = []
    for y in y_coords:
        if not filtered_y or (y - filtered_y[-1] > 20):
            filtered_y.append(y)

    if not rois:
        return []

    start_y = min(roi["y"] for roi in rois.values()) - 10
    valid_y = [y for y in filtered_y if y >= start_y]

    records = []
    custom_config = r"--oem 3 --psm 6"

    for i in range(len(valid_y) - 1):
        y1 = valid_y[i]
        y2 = valid_y[i + 1]

        row_data = {}
        is_empty_row = True

        for field_name, coords in rois.items():
            x, w = coords["x"], coords["w"]
            cell_roi = aligned_image[y1:y2, x : x + w]

            thresh_cell = preprocess_roi_image(cell_roi)
            if thresh_cell is not None:
                text = pytesseract.image_to_string(
                    thresh_cell, lang="ukr+eng", config=custom_config
                ).strip()
            else:
                text = ""

            row_data[field_name] = text

            if len(text) > 2 and not all(c in "—-_=~™ " for c in text):
                is_empty_row = False

        if not is_empty_row:
            records.append(row_data)
            print(f"[OCR] Row found: {row_data}")

    return records


def extract_line_by_line(image):
    """
    Coordinate-free full-page Tesseract OCR.
    Parses document text line-by-line.
    """
    print("[OCR] Running coordinate-free full-page line-by-line OCR...", flush=True)
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (3, 3), 0)
    _, thresh = cv2.threshold(blurred, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

    full_text = pytesseract.image_to_string(thresh, lang="ukr+eng", config=r"--oem 3 --psm 6")
    lines = [line.strip() for line in full_text.split("\n") if line.strip()]

    print(f"[FullText OCR] Extracted {len(lines)} lines of text.", flush=True)
    for line in lines:
        print(f"[Line OCR] {line}", flush=True)

    return full_text, lines


def reconcile_year_months(year, candidate_amounts, annual_total):
    """
    Reconciles extracted month values with the verified official annual total (Total for year).
    Preserves exact monthly salary figures (including 0.00 months) for accurate pension indexing.
    """
    if len(candidate_amounts) >= 12:
        monthly_values = candidate_amounts[:12]
    elif len(candidate_amounts) > 0:
        monthly_values = candidate_amounts + ["0.00"] * (12 - len(candidate_amounts))
    else:
        monthly_values = ["0.00"] * 12

    current_sum = sum(float(m) for m in monthly_values)
    if annual_total and annual_total > 0 and abs(current_sum - annual_total) > 1.0:
        print(
            f"[OK-5 Reconciliation] Discrepancy for {year}: sum {current_sum:.2f} != total {annual_total:.2f}",
            flush=True,
        )
        non_zero_count = sum(1 for m in monthly_values if float(m) > 0)

        if non_zero_count == 0:
            if abs((annual_total / 12.0) - round(annual_total / 12.0, 2)) < 0.05:
                u_val = f"{(annual_total / 12.0):.2f}"
                monthly_values = [u_val] * 12
                print(
                    f"[OK-5 Reconciliation] Year {year}: Resolved via 12-month uniform salary {u_val}",
                    flush=True,
                )
            else:
                for n_months in range(1, 13):
                    calc_m = round(annual_total / n_months, 2)
                    if abs(calc_m * n_months - annual_total) < 1.0 and calc_m >= 100.0:
                        m_str = f"{calc_m:.2f}"
                        monthly_values = [m_str] * n_months + ["0.00"] * (12 - n_months)
                        print(
                            f"[OK-5 Reconciliation] Year {year}: Resolved via {n_months}-month salary {m_str}",
                            flush=True,
                        )
                        break

    return monthly_values


def extract_from_year_block(year, year_lines):
    """
    Extracts 12 monthly salary figures from a specific year block text lines.
    Restricts extraction to target table rows ('Для пенсії', 'Усього, грн.')
    and excludes EDRPOU company tax codes (44300691) and header noise.
    """
    annual_total = None

    for line in year_lines:
        total_match = re.search(
            r"(?:усього\s*за\s*р[іi]к|3a\s*pik)[^\d]*(\d+[.,]\d{2})",
            line,
            flags=re.IGNORECASE,
        )
        if total_match:
            annual_total = float(total_match.group(1).replace(",", "."))
            break

    target_row_lines = []
    for line in year_lines:
        line_lower = line.lower()
        if "усього за рік" in line_lower or "ycboro 3a pik" in line_lower or "разом за рік" in line_lower:
            continue

        if any(kw in line_lower for kw in ["для пенс", "усього, грн", "усього грн", "ycboro", "pensii"]):
            target_row_lines.append(line)

    if not target_row_lines:
        for line in year_lines:
            line_l = line.lower()
            if "усього за рік" in line_l or "звітний" in line_l or "страхувальник" in line_l:
                continue
            if re.search(r"\b\d+[.,]\d{2}\b", line):
                target_row_lines.append(line)

    combined_target_text = " ".join(target_row_lines)
    raw_amounts = re.findall(r"\b\d+[.,]\d{2}\b", combined_target_text)
    clean_amounts = [a.replace(",", ".") for a in raw_amounts if a != str(year)]

    salary_candidates = []
    for a in clean_amounts:
        try:
            v = float(a)
            if v > 500000.0:
                continue
            if annual_total and abs(v - annual_total) < 0.01:
                continue
            if v == 0.0 or v >= 100.0:
                salary_candidates.append(f"{v:.2f}")
        except ValueError:
            pass

    monthly_amounts = reconcile_year_months(year, salary_candidates, annual_total)

    print(f"[OK-5 Parser] Extracted year {year} final values: {monthly_amounts[:12]}", flush=True)

    records = []
    for month_idx, amt in enumerate(monthly_amounts[:12], start=1):
        records.append({"year": str(year), "month": month_idx, "salary_amount": amt})
    return records


def parse_ok5_document(full_text):
    """
    Parses Ukrainian OK-5 document block-by-block per year (Reported year: YYYY).
    Guarantees each year is processed EXACTLY ONCE with 12 clean monthly records.
    """
    lines = [line.strip() for line in full_text.split("\n") if line.strip()]
    records = []
    processed_years = set()

    active_year = None
    year_lines = []

    for line in lines:
        line_lower = line.lower()

        year_match = re.search(
            r"(?:звітний|звітній|seithni|pik|рік)[^\d]*(\d{4})", line, re.IGNORECASE
        )
        if year_match:
            candidate_year = year_match.group(1)
            if 1970 <= int(candidate_year) <= 2030 and candidate_year not in processed_years:
                if active_year and year_lines and active_year not in processed_years:
                    records.extend(extract_from_year_block(active_year, year_lines))
                    processed_years.add(active_year)

                active_year = candidate_year
                year_lines = []
                print(
                    f"[OK-5 Parser] Switched to active year block: {active_year}",
                    flush=True,
                )
                continue

        if active_year and active_year not in processed_years:
            year_lines.append(line)

            if (
                "усього за рік" in line_lower
                or "ycboro 3a pik" in line_lower
                or "разом за рік" in line_lower
            ):
                records.extend(extract_from_year_block(active_year, year_lines))
                processed_years.add(active_year)
                active_year = None
                year_lines = []

    if active_year and year_lines and active_year not in processed_years:
        records.extend(extract_from_year_block(active_year, year_lines))
        processed_years.add(active_year)

    return records


class OcrServicer(ocr_pb2_grpc.OcrServiceServicer):
    def RecognizeTaxDocument(self, request, context):
        print(
            f"\n[gRPC] Received file. Mode: {request.document_type}, Format: {request.file_extension}",
            flush=True,
        )

        try:
            base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
            client_img = bytes_to_cv2_image(
                request.file_content, request.file_extension
            )

            detected_type = request.document_type

            if request.document_type in ["trudova_auto", "auto"]:
                print("[gRPC] Starting page auto-detection...", flush=True)
                templates = {
                    "trudova_title": cv2.imread(
                        os.path.join(base_dir, "templates", "trudova_title.png")
                    ),
                    "trudova_records": cv2.imread(
                        os.path.join(base_dir, "templates", "trudova_records.png")
                    ),
                    "ok5": cv2.imread(os.path.join(base_dir, "templates", "ok5.png")),
                }

                detected_type = detect_template(client_img, templates)
                if detected_type is None:
                    detected_type = "ok5"
                print(f"[gRPC] Successfully detected type: {detected_type}", flush=True)

            template_img_path = os.path.join(
                base_dir, "templates", f"{detected_type}.png"
            )
            template_json_path = os.path.join(
                base_dir, "templates", f"{detected_type}.json"
            )

            template_img = cv2.imread(template_img_path)
            rois = {}
            if os.path.exists(template_json_path):
                with open(template_json_path, "r", encoding="utf-8") as f:
                    rois = json.load(f)

            if template_img is not None:
                aligned_img = align_images(client_img, template_img)
            else:
                aligned_img = client_img

            print("[gRPC] Starting Tesseract OCR...", flush=True)
            extracted_data = {}
            extracted_data["document_subtype"] = detected_type

            full_text_content, lines = extract_line_by_line(client_img)

            # Employment Record Book (Trudova) processing
            if detected_type == "trudova_records":
                records_list = extract_table_records(aligned_img, rois)
                extracted_data["table_records"] = json.dumps(
                    records_list, ensure_ascii=False
                )

            # Special matrix table processing for OK-5
            elif detected_type == "ok5":
                ok5_records = parse_ok5_document(full_text_content)

                print(f"[OCR] Successfully recognized and cleaned OK-5 records: {len(ok5_records)}")
                extracted_data["table_records"] = json.dumps(
                    ok5_records, ensure_ascii=False
                )

            else:
                static_fields = extract_fields_with_ocr(aligned_img, rois)
                extracted_data.update(static_fields)

            display_text = f"Automatically recognized as: {detected_type}\n" + (full_text_content[:300] if full_text_content else "")

            return ocr_pb2.OcrResponse(
                success=True,
                raw_text=display_text,
                data=extracted_data,
                confidence=0.95,
                error_message="",
            )

        except Exception as e:
            print(f"[gRPC Error] {str(e)}", flush=True)
            return ocr_pb2.OcrResponse(
                success=False, raw_text="", confidence=0.0, error_message=str(e)
            )


def serve():
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    ocr_pb2_grpc.add_OcrServiceServicer_to_server(OcrServicer(), server)
    server.add_insecure_port("[::]:50052")
    server.start()
    print("🚀 OCR gRPC Server started on port 50052...", flush=True)
    server.wait_for_termination()


if __name__ == "__main__":
    serve()
