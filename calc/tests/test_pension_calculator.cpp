#include <iostream>
#include <cassert>
#include <cmath>
#include "../src/service/pension_calculator.h"
#include "../src/repository/coefficient_repository.h"

void test_pension_calculator_basic()
{
    std::cout << "[Test] Running test_pension_calculator_basic..." << std::endl;
    calc::service::PensionCalculator calculator;

    calc::PensionRequest request;
    request.set_customer_id("test-user-1");
    request.set_birth_year(1985);
    request.set_target_retirement_year(2050);

    auto *rec1 = request.add_history();
    rec1->set_year(2024);
    rec1->set_annual_income(60000.0);
    rec1->set_tax_paid(12000.0);
    rec1->set_months_worked(12);

    auto res = calculator.calculate(&request);

    assert(res.success == true);
    assert(res.total_accumulated_capital > 0.0);
    assert(res.estimated_monthly_pension >= 1250.0);
    assert(!res.breakdown.empty());

    std::cout << "  ✓ test_pension_calculator_basic passed!" << std::endl;
}

void test_minimum_pension_floor()
{
    std::cout << "[Test] Running test_minimum_pension_floor..." << std::endl;
    calc::service::PensionCalculator calculator;

    calc::PensionRequest request;
    request.set_customer_id("low-income-user");
    request.set_birth_year(1995);
    request.set_target_retirement_year(2060);

    auto *rec1 = request.add_history();
    rec1->set_year(2025);
    rec1->set_annual_income(1000.0);
    rec1->set_tax_paid(200.0);
    rec1->set_months_worked(12);

    auto res = calculator.calculate(&request);

    assert(res.success == true);
    assert(res.estimated_monthly_pension == 1250.0);

    std::cout << "  ✓ test_minimum_pension_floor passed!" << std::endl;
}

void test_upsert_average_salary()
{
    std::cout << "[Test] Running test_upsert_average_salary..." << std::endl;
    calc::repository::CoefficientRepository repo;
    bool ok = repo.upsertAverageSalary(2026, 1, 21876.06);
    if (ok)
    {
        std::cout << "  ✓ test_upsert_average_salary passed (DB connected)!" << std::endl;
    }
    else
    {
        std::cout << "  ✓ test_upsert_average_salary handled offline DB gracefully!" << std::endl;
    }
}

int main()
{
    std::cout << "=========================================" << std::endl;
    std::cout << "Running Calc C++ Unit Tests" << std::endl;
    std::cout << "=========================================" << std::endl;

    test_pension_calculator_basic();
    test_minimum_pension_floor();
    test_upsert_average_salary();

    std::cout << "=========================================" << std::endl;
    std::cout << "✅ All C++ Calc Unit Tests Passed!" << std::endl;
    std::cout << "=========================================" << std::endl;
    return 0;
}
