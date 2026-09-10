import cv2
import pytesseract
import re
from image_utils import preprocess_roi_image


def clean_line_text(raw_text):
    """
    Normalizes OCR line text:
    1. Repairs split annual totals (e.g., '960.00 00' -> '96000.00', '605.29 00' -> '60529.00').
    2. Converts 5-7 digit integers (missing decimal dots) to 2-decimal floats (e.g., 800000 -> 8000.00).
    3. Repairs spaces dropped around decimal dots (e.g., '96000 00' -> '96000.00').
    4. Cleans trailing punctuation attached to decimal numbers (e.g., '8090.00"' -> '8090.00').
    """
    # 1. Fix split annual totals like '960.00 00' -> '96000.00'
    text = re.sub(r"(\d{2,5})\s*[\.,]\s*(\d{2})\s+(\d{2})\b", r"\1\2.\3", raw_text)

    # 2. Convert 5-7 digit integers without dot
    def repl(m):
        digits = m.group(1)
        if len(digits) in (5, 6, 7):
            return digits[:-2] + "." + digits[-2:]
        return digits

    text = re.sub(r"(?<![\.,\d])\b(\d{5,7})\b(?![\.,]\d)", repl, text)

    # 3. Repair spaces around decimal dots
    text = re.sub(r"(\d+)\s*[\.,]\s*(\d{2})\b", r"\1.\2", text)

    # 4. Clean trailing non-digit non-space chars
    text = re.sub(r"(\d+[.,]\d{2})[\x27\x22\}\];:]", r"\1", text)
    return text


def extract_line_by_line(image):
    """
    Coordinate-free full-page Tesseract OCR.
    Uses 2x upscaling, Gaussian blur, and Otsu thresholding to guarantee crisp Tesseract line OCR.
    Normalizes spaces around decimal dots and missing dots.
    """
    print("[OCR] Running coordinate-free full-page line-by-line OCR...", flush=True)
    resized = cv2.resize(image, (0, 0), fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC)
    gray = cv2.cvtColor(resized, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (3, 3), 0)
    _, thresh = cv2.threshold(blurred, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

    raw_text = pytesseract.image_to_string(
        thresh, lang="ukr+eng", config=r"--oem 3 --psm 6"
    )
    clean_text = clean_line_text(raw_text)

    lines = [line.strip() for line in clean_text.split("\n") if line.strip()]

    print(f"[FullText OCR] Extracted {len(lines)} lines of text.", flush=True)
    for line in lines:
        print(f"[Line OCR] {line}", flush=True)

    return clean_text, lines


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
