#include <iostream>
#include <cassert>
#include <cmath>
#include "../../src/service/pension_calculator.h"

void test_solidarity_pension_pipeline_stage1_to_5()
{
    std::cout << "[Unit Test] Running test_solidarity_pension_pipeline_stage1_to_5..." << std::endl;
    calc::repository::CoefficientRepository repo(true);
    calc::service::PensionCalculator calculator(repo);

    calc::CalculatePensionRequest request;
    request.set_customer_id("ua-citizen-1001");
    request.set_gender(calc::Gender::MALE);
    request.set_date_of_birth("1960-05-15");
    request.set_retirement_date("2024-06-01");
    request.set_pension_type(calc::PensionType::OLD_AGE);
    request.set_zp_macroeconomic_average(13559.41);
    request.set_enable_optimization_rule(true);

    // 40 years of service (480 months) -> Extra 5 years over required 35 (420 months) for men
    auto *period = request.add_employment_history();
    period->set_start_date("1984-01-01");
    period->set_end_date("2023-12-31");
    period->set_multiplier(1.0);

    // Salary history: 12 months with 25000 UAH, average national salary 18000 UAH
    for (int m = 1; m <= 12; ++m)
    {
        repo.upsertAverageSalary(2023, m, 18000.0);
        auto *s = request.add_salary_history();
        s->set_year(2023);
        s->set_month(m);
        s->set_amount(25000.0);
    }

    // Add Combat Veteran Benefit (УБД)
    request.add_benefits(calc::BenefitType::COMBAT_VETERAN);

    // Add Subsistence Minimums
    request.mutable_subsistence_minimums()->set_for_disabled_persons(2361.0);
    request.mutable_subsistence_minimums()->set_general_minimum(2920.0);

    auto res = calculator.calculate(&request);

    assert(res.success == true);
    assert(res.total_service_months == 480);
    assert(res.ks_service_coefficient == 480.0 / 1200.0); // 0.40
    assert(res.base_pension > 0.0);
    assert(res.pension_type_modifier == 1.0);
    assert(res.extra_service_allowance > 0.0);                           // Allowance for 5 extra years
    assert(res.total_benefit_surcharges == 2361.0 * 0.25);               // COMBAT_VETERAN (+25%)
    assert(res.final_pension >= 2361.0 && res.final_pension <= 23610.0); // Clamped between min & max limits
    assert(!res.calculation_logs.empty());

    std::cout << "  ✓ test_solidarity_pension_pipeline_stage1_to_5 passed! (Final Pension: "
              << res.final_pension << " UAH)" << std::endl;
}

void test_disability_pension_modifiers()
{
    std::cout << "[Unit Test] Running test_disability_pension_modifiers..." << std::endl;
    calc::repository::CoefficientRepository repo(true);
    repo.upsertSubsistenceLimits(2024, 2361.0, 2920.0);
    calc::service::PensionCalculator calculator(repo);

    calc::CalculatePensionRequest request;
    request.set_customer_id("disability-group-2-user");
    request.set_gender(calc::Gender::FEMALE);
    request.set_retirement_date("2024-06-01");
    request.set_pension_type(calc::PensionType::DISABILITY);
    request.set_disability_group(calc::DisabilityGroup::GROUP_2);
    request.set_zp_macroeconomic_average(13559.41);

    auto *period = request.add_employment_history();
    period->set_start_date("2000-01-01");
    period->set_end_date("2020-12-31");
    period->set_multiplier(1.0);

    auto *s = request.add_salary_history();
    s->set_year(2020);
    s->set_month(1);
    s->set_amount(15000.0);

    auto res = calculator.calculate(&request);
    if (!res.success)
    {
        std::cout << "[ERROR] calculation failed: " << res.error_message << std::endl;
    }

    assert(res.success == true);
    assert(res.pension_type_modifier == 0.90); // 90% for Group 2

    std::cout << "  ✓ test_disability_pension_modifiers passed!" << std::endl;
}

int main()
{
    std::cout << "=========================================" << std::endl;
    std::cout << "Running Ukrainian Pension Calculator Unit Tests" << std::endl;
    std::cout << "=========================================" << std::endl;

    test_solidarity_pension_pipeline_stage1_to_5();
    test_disability_pension_modifiers();

    std::cout << "=========================================" << std::endl;
    std::cout << "✅ All Ukrainian Pension Calculator Unit Tests Passed!" << std::endl;
    std::cout << "=========================================" << std::endl;
    return 0;
}
