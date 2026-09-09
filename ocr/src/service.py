import os
import json
import cv2
import ocr_pb2
import ocr_pb2_grpc
from image_utils import bytes_to_cv2_image, detect_template, align_images
from ocr_engine import (
    extract_line_by_line,
    extract_fields_with_ocr,
    extract_table_records,
)
from parsers.ok5_parser import parse_ok5_document


class OcrServicer(ocr_pb2_grpc.OcrServiceServicer):
    def _detect_and_align(self, client_img, requested_type, base_dir):
        """Detects document subtype (if auto) and aligns client image against template."""
        detected_type = requested_type

        if requested_type in ["trudova_auto", "auto"]:
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
            detected_type = detect_template(client_img, templates) or "ok5"
            print(f"[gRPC] Successfully detected type: {detected_type}", flush=True)

        template_img_path = os.path.join(base_dir, "templates", f"{detected_type}.png")
        template_json_path = os.path.join(
            base_dir, "templates", f"{detected_type}.json"
        )

        template_img = cv2.imread(template_img_path)
        rois = {}
        if os.path.exists(template_json_path):
            with open(template_json_path, "r", encoding="utf-8") as f:
                rois = json.load(f)

        aligned_img = (
            align_images(client_img, template_img)
            if template_img is not None
            else client_img
        )
        return detected_type, aligned_img, rois

    def _process_document_by_subtype(
        self, detected_type, client_img, aligned_img, rois, full_text_content
    ):
        """Dispatches extraction logic based on document subtype."""
        extracted_data = {"document_subtype": detected_type}

        if detected_type == "trudova_records":
            records_list = extract_table_records(aligned_img, rois)
            extracted_data["table_records"] = json.dumps(
                records_list, ensure_ascii=False
            )
            return extracted_data

        if detected_type == "ok5":
            ok5_records = parse_ok5_document(full_text_content)
            print(
                f"[OCR] Successfully recognized and cleaned OK-5 records: {len(ok5_records)}"
            )
            extracted_data["table_records"] = json.dumps(
                ok5_records, ensure_ascii=False
            )
            return extracted_data

        static_fields = extract_fields_with_ocr(aligned_img, rois)
        extracted_data.update(static_fields)
        return extracted_data

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

            detected_type, aligned_img, rois = self._detect_and_align(
                client_img, request.document_type, base_dir
            )

            print("[gRPC] Starting Tesseract OCR...", flush=True)
            full_text_content, lines = extract_line_by_line(client_img)

            extracted_data = self._process_document_by_subtype(
                detected_type, client_img, aligned_img, rois, full_text_content
            )

            display_text = f"Automatically recognized as: {detected_type}\n" + (
                full_text_content[:300] if full_text_content else ""
            )

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
