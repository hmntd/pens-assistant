cmake_minimum_required(VERSION 3.15)
project(calc_service)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(gRPC REQUIRED)
find_package(Protobuf REQUIRED)
find_package(PkgConfig REQUIRED)

pkg_check_modules(PQXX REQUIRED libpqxx)

include_directories(
    ${CMAKE_CURRENT_SOURCE_DIR}/src
    ${PQXX_INCLUDE_DIRS}
)

add_executable(calc_service
    src/main.cpp
    src/db/db_config.cpp
    src/repository/coefficient_repository.cpp
    src/service/pension_calculator.cpp
    src/grpc/calc_service_impl.cpp
    src/calc.pb.cc 
    src/calc.grpc.pb.cc
)

target_link_libraries(calc_service
    gRPC::grpc++
    protobuf::libprotobuf
    ${PQXX_LIBRARIES}
)

add_executable(calc_tests
    tests/test_pension_calculator.cpp
    src/db/db_config.cpp
    src/repository/coefficient_repository.cpp
    src/service/pension_calculator.cpp
    src/calc.pb.cc 
    src/calc.grpc.pb.cc
)

target_link_libraries(calc_tests
    gRPC::grpc++
    protobuf::libprotobuf
    ${PQXX_LIBRARIES}
)