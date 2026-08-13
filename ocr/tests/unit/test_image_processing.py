import pytest
import cv2
import numpy as np
from main import bytes_to_cv2_image, detect_template, align_images


def create_dummy_cv2_image(width=200, height=200, color=(255, 255, 255)):
    """Створює синтетичне зображення OpenCV у пам'яті."""
    img = np.zeros((height, width, 3), dtype=np.uint8)
    img[:] = color
    cv2.rectangle(img, (20, 20), (80, 80), (0, 0, 0), -1)
    cv2.circle(img, (150, 150), 30, (128, 128, 128), -1)
    return img


def test_bytes_to_cv2_image_png():
    """Перевіряє декодування масиву байтів зображення PNG в OpenCV numpy array."""
    dummy_img = create_dummy_cv2_image()
    success, encoded_bytes = cv2.imencode(".png", dummy_img)
    assert success is True

    result_img = bytes_to_cv2_image(encoded_bytes.tobytes(), "png")
    assert result_img is not None
    assert isinstance(result_img, np.ndarray)
    assert result_img.shape[0] == 200
    assert result_img.shape[1] == 200


def test_bytes_to_cv2_image_invalid():
    """Перевіряє обробку некоректних байтів зображення."""
    invalid_bytes = b"invalid_image_payload_data"
    result_img = bytes_to_cv2_image(invalid_bytes, "png")
    assert result_img is None


def test_detect_template():
    """Перевіряє порівняння та класифікацію фото по шаблону за ORB точками."""
    template_img = create_dummy_cv2_image(300, 300)
    client_img = create_dummy_cv2_image(300, 300)

    templates_dict = {
        "template_a": template_img,
    }

    best_match = detect_template(client_img, templates_dict)
    assert best_match == "template_a"


def test_align_images():
    """Перевіряє гомографічне вирівнювання кривого або однакового фото по шаблону."""
    template = create_dummy_cv2_image(200, 200)
    client_img = create_dummy_cv2_image(200, 200)

    aligned = align_images(client_img, template, max_features=1000)
    assert aligned is not None
    assert isinstance(aligned, np.ndarray)
    assert aligned.shape == template.shape
