#include <iostream>
#include <cassert>
#include <memory>
#include "../../src/grpc/calc_service_impl.h"

void test_grpc_sync_average_salaries() {
    std::cout << "[Feature Test] Running test_grpc_sync_average_salaries..." << std::endl;
    calc::grpc_service::CalcServiceImpl service;
    grpc::ServerContext context;

    calc::SyncAverageSalariesRequest request;
    calc::SyncAverageSalariesResponse response;

    auto* item1 = request.add_salaries();
    item1->set_year(2026);
    item1->set_month(1);
    item1->set_amount(21876.06);

    auto status = service.SyncAverageSalaries(&context, &request, &response);

    assert(status.ok());
    assert(response.success() == true);
    assert(response.processed_count() >= 0);

    std::cout << "  ✓ test_grpc_sync_average_salaries passed!" << std::endl;
}

void test_grpc_calculate_pension() {
    std::cout << "[Feature Test] Running test_grpc_calculate_pension..." << std::endl;
    calc::grpc_service::CalcServiceImpl service;
    grpc::ServerContext context;

    calc::PensionRequest request;
    calc::PensionResponse response;

    request.set_customer_id("feature-test-user");
    request.set_birth_year(1980);
    request.set_target_retirement_year(2045);

    auto status = service.CalculatePension(&context, &request, &response);

    assert(status.ok());
    assert(response.success() == true);
    assert(response.estimated_monthly_pension() >= 1250.0);

    std::cout << "  ✓ test_grpc_calculate_pension passed!" << std::endl;
}

int main() {
    std::cout << "=========================================" << std::endl;
    std::cout << "Running Calc Service Feature Tests (gRPC Handlers)" << std::endl;
    std::cout << "=========================================" << std::endl;

    test_grpc_sync_average_salaries();
    test_grpc_calculate_pension();

    std::cout << "=========================================" << std::endl;
    std::cout << "✅ All Calc Service gRPC Feature Tests Passed!" << std::endl;
    std::cout << "=========================================" << std::endl;
    return 0;
}
