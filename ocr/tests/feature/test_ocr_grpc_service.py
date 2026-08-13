import pytest
import cv2
import numpy as np
import ocr_pb2
from main import OcrServicer


class DummyContext:
    def __init__(self):
        self.code = None
        self.details = None


def test_recognize_tax_document_invalid_bytes():
    """Тестує gRPC метод RecognizeTaxDocument з некоректними байтами (повертає success=False без падіння)."""
    servicer = OcrServicer()
    request = ocr_pb2.OcrRequest(
        file_content=b"invalid_corrupted_file_bytes",
        file_extension="pdf",
        document_type="income_certificate",
    )
    context = DummyContext()

    response = servicer.RecognizeTaxDocument(request, context)
    assert response is not None
    assert response.success is False
    assert len(response.error_message) > 0


def test_recognize_tax_document_valid_image():
    """Тестує gRPC метод RecognizeTaxDocument з валідними байтами зображення."""
    dummy_img = np.ones((200, 200, 3), dtype=np.uint8) * 255
    success, encoded_bytes = cv2.imencode(".png", dummy_img)
    assert success is True

    servicer = OcrServicer()
    request = ocr_pb2.OcrRequest(
        file_content=encoded_bytes.tobytes(),
        file_extension="png",
        document_type="income_certificate",
    )
    context = DummyContext()

    response = servicer.RecognizeTaxDocument(request, context)
    assert response is not None
    assert isinstance(response.success, bool)
