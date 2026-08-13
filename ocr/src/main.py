import cv2
import numpy as np
import pytesseract
import json
import os
import grpc
from concurrent import futures
from pdf2image import convert_from_bytes
import ocr_pb2
import ocr_pb2_grpc


def bytes_to_cv2_image(file_bytes, extension):
    """Конвертує сирі байти (PDF або Зображення) у формат OpenCV (numpy array)."""
    if extension.lower() == "pdf":
        images = convert_from_bytes(file_bytes, first_page=1, last_page=1)
        open_cv_image = np.array(images[0])
        return open_cv_image[:, :, ::-1].copy()
    else:
        nparr = np.frombuffer(file_bytes, np.uint8)
        return cv2.imdecode(nparr, cv2.IMREAD_COLOR)


def detect_template(client_img, templates_dict):
    """Порівнює клієнтське фото з усіма доступними шаблонами за ключовими точками (ORB)."""
    best_match_count = 0
    best_template_name = None

    client_gray = cv2.cvtColor(client_img, cv2.COLOR_BGR2GRAY)
    orb = cv2.ORB_create(3000)
    kpsA, descsA = orb.detectAndCompute(client_gray, None)

    matcher = cv2.DescriptorMatcher_create(cv2.DESCRIPTOR_MATCHER_BRUTEFORCE_HAMMING)

    for name, template_img in templates_dict.items():
        template_gray = cv2.cvtColor(template_img, cv2.COLOR_BGR2GRAY)
        _, descsB = orb.detectAndCompute(template_gray, None)

        if descsA is None or descsB is None:
            continue

        matches = matcher.match(descsA, descsB, None)
        good_matches = [m for m in matches if m.distance < 50]

        print(f"[Classifier] Шаблон '{name}' дав {len(good_matches)} точок збігу.")

        if len(good_matches) > best_match_count:
            best_match_count = len(good_matches)
            best_template_name = name

    return best_template_name


def align_images(image, template, max_features=5000, keep_percent=0.2):
    """Вирівнює (натягує) криве фото по ідеальному шаблону."""
    image_gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    template_gray = cv2.cvtColor(template, cv2.COLOR_BGR2GRAY)

    orb = cv2.ORB_create(max_features)
    kpsA, descsA = orb.detectAndCompute(image_gray, None)
    kpsB, descsB = orb.detectAndCompute(template_gray, None)

    method = cv2.DESCRIPTOR_MATCHER_BRUTEFORCE_HAMMING
    matcher = cv2.DescriptorMatcher_create(method)
    matches = matcher.match(descsA, descsB, None)

    matches = sorted(matches, key=lambda x: x.distance)
    keep = int(len(matches) * keep_percent)
    matches = matches[:keep]

    ptsA = np.zeros((len(matches), 2), dtype="float")
    ptsB = np.zeros((len(matches), 2), dtype="float")
    for i, m in enumerate(matches):
        ptsA[i] = kpsA[m.queryIdx].pt
        ptsB[i] = kpsB[m.trainIdx].pt

    H, mask = cv2.findHomography(ptsA, ptsB, method=cv2.RANSAC)
    h, w = template.shape[:2]

    return cv2.warpPerspective(image, H, (w, h))


def extract_fields_with_ocr(aligned_image, rois):
    """Вирізає статичні зони (титулка) і читає їх через Tesseract."""
    extracted_data = {}
    custom_config = r"--oem 3 --psm 7"

    for field_name, coords in rois.items():
        x, y, w, h = coords["x"], coords["y"], coords["w"], coords["h"]
        roi_cropped = aligned_image[y : y + h, x : x + w]

        gray_roi = cv2.cvtColor(roi_cropped, cv2.COLOR_BGR2GRAY)
        _, thresh_roi = cv2.threshold(gray_roi, 150, 255, cv2.THRESH_BINARY)

        text = pytesseract.image_to_string(
            thresh_roi, lang="ukr+eng", config=custom_config
        )
        extracted_data[field_name] = text.strip()
        print(f"[OCR] Поле '{field_name}': {extracted_data[field_name]}")

    return extracted_data


def extract_table_records(aligned_image, rois):
    """Шукає горизонтальні лінії таблиці, розрізає її на рядки та читає комірки."""
    print("[OCR] Запуск динамічного парсингу таблиці...", flush=True)
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

            gray_cell = cv2.cvtColor(cell_roi, cv2.COLOR_BGR2GRAY)
            _, thresh_cell = cv2.threshold(gray_cell, 150, 255, cv2.THRESH_BINARY)

            text = pytesseract.image_to_string(
                thresh_cell, lang="ukr+eng", config=custom_config
            ).strip()
            row_data[field_name] = text

            if len(text) > 2:
                is_empty_row = False

        if not is_empty_row:
            records.append(row_data)
            print(f"[OCR] Знайдено рядок: {row_data}")

    return records


class OcrServicer(ocr_pb2_grpc.OcrServiceServicer):
    def RecognizeTaxDocument(self, request, context):
        print(
            f"\n[gRPC] Отримано файл. Режим: {request.document_type}, Формат: {request.file_extension}",
            flush=True,
        )

        try:
            base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
            client_img = bytes_to_cv2_image(
                request.file_content, request.file_extension
            )

            detected_type = request.document_type

            if request.document_type == "trudova_auto":
                print("[gRPC] Запуск авто-детекту сторінки...", flush=True)
                templates = {
                    "trudova_title": cv2.imread(
                        os.path.join(base_dir, "templates", "trudova_title.png")
                    ),
                    "trudova_records": cv2.imread(
                        os.path.join(base_dir, "templates", "trudova_records.png")
                    ),
                }

                detected_type = detect_template(client_img, templates)
                if detected_type is None:
                    raise ValueError("Не вдалося розпізнати тип документа.")
                print(f"[gRPC] Успішно визначено тип: {detected_type}", flush=True)

            template_img_path = os.path.join(
                base_dir, "templates", f"{detected_type}.png"
            )
            template_json_path = os.path.join(
                base_dir, "templates", f"{detected_type}.json"
            )

            template_img = cv2.imread(template_img_path)
            with open(template_json_path, "r", encoding="utf-8") as f:
                rois = json.load(f)

            print("[gRPC] Виконую вирівнювання (Alignment)...", flush=True)
            aligned_img = align_images(client_img, template_img)

            print("[gRPC] Запускаю Tesseract OCR...", flush=True)
            extracted_data = {}
            extracted_data["document_subtype"] = detected_type

            if detected_type == "trudova_records":
                records_list = extract_table_records(aligned_img, rois)
                extracted_data["table_records"] = json.dumps(
                    records_list, ensure_ascii=False
                )
            else:
                static_fields = extract_fields_with_ocr(aligned_img, rois)
                extracted_data.update(static_fields)

            return ocr_pb2.OcrResponse(
                success=True,
                raw_text=f"Автоматично розпізнано як: {detected_type}",
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
    print("🚀 OCR gRPC Server запущено на порту 50052...", flush=True)
    server.wait_for_termination()


if __name__ == "__main__":
    serve()
