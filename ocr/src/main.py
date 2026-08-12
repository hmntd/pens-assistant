import grpc
from concurrent import futures
import time
import sys

import ocr_pb2
import ocr_pb2_grpc

class OcrServicer(ocr_pb2_grpc.OcrServiceServicer):
    def SayHello(self, request, context):
        print(f"[OCR] Received request from: {request.name}", flush=True)
        return ocr_pb2.HelloResponse(
            message=f"Hello world! It's Python OCR service."
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