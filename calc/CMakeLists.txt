cmake_minimum_required(VERSION 3.15)
project(calc_service)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(gRPC REQUIRED)
find_package(Protobuf REQUIRED)

add_executable(calc_service
    src/main.cpp
    src/calc.pb.cc 
    src/calc.grpc.pb.cc
)

target_link_libraries(calc_service gRPC::grpc++ protobuf::libprotobuf)