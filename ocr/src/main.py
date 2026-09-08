import grpc
from concurrent import futures
import ocr_pb2_grpc

# Re-exports for 100% backward compatibility with unit tests and external imports
from image_utils import (
    bytes_to_cv2_image,
    detect_template,
    align_images,
    preprocess_roi_image,
)
from ocr_engine import (
    clean_line_text,
    extract_line_by_line,
    extract_fields_with_ocr,
    extract_table_records,
)
from parsers.ok5_parser import (
    extract_from_year_block,
    parse_ok5_document,
)
from service import OcrServicer


def serve():
    """Starts the OCR gRPC server on port 50052."""
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    ocr_pb2_grpc.add_OcrServiceServicer_to_server(OcrServicer(), server)
    server.add_insecure_port("[::]:50052")
    server.start()
    print("🚀 OCR gRPC Server started on port 50052...", flush=True)
    server.wait_for_termination()


if __name__ == "__main__":
    serve()
