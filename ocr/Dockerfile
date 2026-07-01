FROM python:3.12-slim

WORKDIR /app

RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    libtesseract-dev \
    libgl1 \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

COPY ocr/requirements.txt .

RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt && \
    pip install --no-cache-dir grpcio grpcio-tools opencv-python-headless easyocr pytesseract

COPY ocr/ /app/ocr/
COPY protos/ /app/protos/

RUN python -m grpc_tools.protoc \
    -I/app/protos \
    --python_out=/app/ocr/src \
    --grpc_python_out=/app/ocr/src \
    /app/protos/ocr.proto

ENV PYTHONPATH=/app/ocr/src

EXPOSE 50052

CMD ["python", "ocr/src/main.py"]