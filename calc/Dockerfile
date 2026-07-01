FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /app

RUN apt-get update && apt-get install -y \
    build-essential \
    cmake \
    libgrpc++-dev \
    protobuf-compiler-grpc \
    libprotobuf-dev \
    libpq-dev \
    libpqxx-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

COPY calc/ /app/
COPY protos/ /app/protos/

RUN mkdir -p build && cd build \
    && cmake .. \
    && make -j$(nproc)

EXPOSE 50051

CMD ["./build/calc_service"]