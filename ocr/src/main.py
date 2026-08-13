import grpc
from concurrent import futures
import time
import sys

import ocr_pb2
import ocr_pb2_grpc

class OcrServicer(ocr_pb2_grpc.OcrServiceServicer):
    def RecognizeTaxDocument(self, request, context):
        print(f"[OCR] Processing document type: {request.document_type}, ext: {request.file_extension}", flush=True)
        return ocr_pb2.OcrResponse(
            success=True,
            raw_text="Simulated OCR Text: Income Certificate 2024. Total Income: $105,000",
            data={
                "taxpayer_name": "John Doe",
                "year": "2024",
                "annual_income": "105000",
                "document_type": request.document_type
            },
            confidence=0.98,
            error_message=""
        )

def serve():
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    ocr_pb2_grpc.add_OcrServiceServicer_to_server(OcrServicer(), server)

    server.add_insecure_port('[::]:50052')
    server.start()
    print("OCR gRPC Server is running on port 50052...", flush=True)

    server.wait_for_termination()

if __name__ == '__main__':
    serve()