import pytest
import cv2
import numpy as np
from main import extract_fields_with_ocr, extract_table_records


def create_text_image(text="2024", width=200, height=50):
    """Створює синтетичне зображення з текстом для перевірки OCR."""
    img = np.ones((height, width, 3), dtype=np.uint8) * 255
    cv2.putText(img, text, (10, 35), cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 0, 0), 2)
    return img


def test_extract_fields_with_ocr():
    """Перевіряє витягування полів з ROI області за допомогою Tesseract OCR."""
    text_img = create_text_image("2024", width=200, height=100)

    rois = {"year": {"x": 0, "y": 0, "w": 200, "h": 100}}

    fields = extract_fields_with_ocr(text_img, rois)
    assert "year" in fields
    assert isinstance(fields["year"], str)


def test_extract_table_records():
    """Перевіряє парсинг горизонтальних ліній та комірок таблиці."""
    table_img = np.ones((300, 500, 3), dtype=np.uint8) * 255
    cv2.line(table_img, (10, 50), (490, 50), (0, 0, 0), 3)
    cv2.line(table_img, (10, 150), (490, 150), (0, 0, 0), 3)
    cv2.line(table_img, (10, 250), (490, 250), (0, 0, 0), 3)

    cv2.putText(
        table_img, "2024", (20, 100), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 0, 0), 2
    )

    rois = {"year": {"x": 10, "y": 50, "w": 200, "h": 100}}

    records = extract_table_records(table_img, rois)
    assert isinstance(records, list)
