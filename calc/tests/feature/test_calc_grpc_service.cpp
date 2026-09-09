#include <iostream>
#include <cassert>
#include <memory>
#include "../../src/grpc/calc_service_impl.h"

void test_grpc_sync_average_salaries() {
    std::cout << "[Feature Test] Running test_grpc_sync_average_salaries..." << std::endl;
    calc::grpc_service::CalcServiceImpl service(true);
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
    calc::grpc_service::CalcServiceImpl service(true);
    grpc::ServerContext context;

    calc::SyncAverageSalariesRequest sync_req;
    calc::SyncAverageSalariesResponse sync_resp;
    auto *sync_item = sync_req.add_salaries();
    sync_item->set_year(2020);
    sync_item->set_month(1);
    sync_item->set_amount(10000.0);
    service.SyncAverageSalaries(&context, &sync_req, &sync_resp);

    calc::CalculatePensionRequest request;
    calc::CalculatePensionResponse response;

    request.set_customer_id("feature-test-user-1002");
    request.set_gender(calc::Gender::MALE);
    request.set_retirement_date("2024-06-01");
    request.set_pension_type(calc::PensionType::OLD_AGE);
    request.set_zp_macroeconomic_average(13559.41);
    request.add_benefits(calc::BenefitType::HONORARY_DONOR);

    auto *period = request.add_employment_history();
    period->set_start_date("2000-01-01");
    period->set_end_date("2020-12-31");
    period->set_multiplier(1.0);

    auto *s = request.add_salary_history();
    s->set_year(2020);
    s->set_month(1);
    s->set_amount(15000.0);

    auto status = service.CalculatePension(&context, &request, &response);

    if (!response.success())
    {
        std::cout << "[ERROR] gRPC CalculatePension failed: " << response.error_message() << std::endl;
    }

    assert(status.ok());
    assert(response.success() == true);
    assert(response.final_pension() >= 2361.0);
    assert(response.total_benefit_surcharges() > 0.0);
    assert(response.applied_benefits_size() == 1);
    assert(response.applied_benefits(0).benefit() == calc::BenefitType::HONORARY_DONOR);

    std::cout << "  ✓ test_grpc_calculate_pension passed! (Final Pension: " 
              << response.final_pension() << " UAH)" << std::endl;
}

void test_grpc_canonical_error_statuses() {
    std::cout << "[Feature Test] Running test_grpc_canonical_error_statuses..." << std::endl;
    calc::grpc_service::CalcServiceImpl service(true);
    grpc::ServerContext context;

    // Test Delete non-existent coefficient returns NOT_FOUND
    calc::DeleteCoefficientRequest del_req;
    del_req.set_id(99999);
    calc::DeleteCoefficientResponse del_resp;
    auto status = service.DeleteCoefficient(&context, &del_req, &del_resp);
    assert(!status.ok());
    assert(status.error_code() == grpc::StatusCode::NOT_FOUND);

    // Test CalculatePension with invalid date format returns FAILED_PRECONDITION
    calc::CalculatePensionRequest calc_req;
    calc_req.set_customer_id("invalid-year-user");
    calc_req.set_retirement_date("invalid-date-string");
    calc::CalculatePensionResponse calc_resp;
    auto calc_status = service.CalculatePension(&context, &calc_req, &calc_resp);
    assert(!calc_status.ok());
    assert(calc_status.error_code() == grpc::StatusCode::FAILED_PRECONDITION);

    std::cout << "  ✓ test_grpc_canonical_error_statuses passed! (Verified NOT_FOUND and FAILED_PRECONDITION)" << std::endl;
}

int main() {
    std::cout << "=========================================" << std::endl;
    std::cout << "Running Calc Service Feature Tests (gRPC Handlers)" << std::endl;
    std::cout << "=========================================" << std::endl;

    test_grpc_sync_average_salaries();
    test_grpc_calculate_pension();
    test_grpc_canonical_error_statuses();

    std::cout << "=========================================" << std::endl;
    std::cout << "✅ All Calc Service gRPC Feature Tests Passed!" << std::endl;
    std::cout << "=========================================" << std::endl;
    return 0;
}
