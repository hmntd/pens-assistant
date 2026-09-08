import cv2
import numpy as np
from pdf2image import convert_from_bytes


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

        print(
            f"[Classifier] Template '{name}' yielded {len(good_matches)} matching keypoints."
        )

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

        H, mask = cv2.findHomography(
            ptsA, ptsB, method=cv2.RANSAC, ransacReprojThreshold=5.0
        )
        if H is None:
            print(
                "[Alignment] Homography matrix is None, using direct resize fallback."
            )
            return cv2.resize(image, (w, h))

        det = abs(np.linalg.det(H[:2, :2]))
        if det < 0.2 or det > 5.0:
            print(
                f"[Alignment] Homography determinant ({det:.2f}) out of bounds, using direct resize fallback."
            )
            return cv2.resize(image, (w, h))

        return cv2.warpPerspective(image, H, (w, h))
    except Exception as e:
        print(f"[Alignment] Alignment error: {e}, using direct resize fallback.")
        return cv2.resize(image, (w, h))


def preprocess_roi_image(roi_cropped):
    """Upscale 2x and perform Gaussian blur + Otsu thresholding for crisp Tesseract OCR."""
    if roi_cropped is None or roi_cropped.size == 0:
        return None
    resized = cv2.resize(
        roi_cropped, (0, 0), fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC
    )
    gray = cv2.cvtColor(resized, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (3, 3), 0)
    _, thresh = cv2.threshold(blurred, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    return thresh
