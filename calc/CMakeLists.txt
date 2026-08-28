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

set(CALC_COMMON_SRCS
    src/db/db_config.cpp
    src/repository/coefficient_repository.cpp
    src/service/pension_calculator.cpp
    src/service/util/date_utils.cpp
    src/service/util/money_format.cpp
    src/service/stages/service_coefficient_stage.cpp
    src/service/stages/wage_coefficient_stage.cpp
    src/service/stages/base_pension_stage.cpp
    src/service/stages/pension_type_stage.cpp
    src/service/stages/extra_service_stage.cpp
    src/service/stages/benefit_surcharge_stage.cpp
    src/service/stages/age_surcharge_stage.cpp
    src/service/stages/legal_bounds_stage.cpp
    src/service/adapters/legacy_request_adapter.cpp
    src/calc.pb.cc
    src/calc.grpc.pb.cc
)

add_executable(calc_service
    src/main.cpp
    src/grpc/calc_service_impl.cpp
    ${CALC_COMMON_SRCS}
)

target_link_libraries(calc_service
    gRPC::grpc++
    protobuf::libprotobuf
    ${PQXX_LIBRARIES}
)

add_executable(unit_calculator_tests
    tests/unit/test_pension_calculator.cpp
    ${CALC_COMMON_SRCS}
)

target_link_libraries(unit_calculator_tests
    gRPC::grpc++
    protobuf::libprotobuf
    ${PQXX_LIBRARIES}
)

add_executable(unit_repository_tests
    tests/unit/test_coefficient_repository.cpp
    ${CALC_COMMON_SRCS}
)

target_link_libraries(unit_repository_tests
    gRPC::grpc++
    protobuf::libprotobuf
    ${PQXX_LIBRARIES}
)

add_executable(feature_grpc_tests
    tests/feature/test_calc_grpc_service.cpp
    src/grpc/calc_service_impl.cpp
    ${CALC_COMMON_SRCS}
)

target_link_libraries(feature_grpc_tests
    gRPC::grpc++
    protobuf::libprotobuf
    ${PQXX_LIBRARIES}
)