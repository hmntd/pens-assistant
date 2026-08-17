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

void test_age_based_surcharges()
{
    std::cout << "[Unit Test] Running test_age_based_surcharges..." << std::endl;
    calc::repository::CoefficientRepository repo(true);
    repo.upsertSubsistenceLimits(2024, 2361.0, 2920.0);
    calc::service::PensionCalculator calculator(repo);

    // Test Case 1: Person age 72 (born 1952-05-15, retiring 2024-06-01) -> +300 UAH
    calc::CalculatePensionRequest req1;
    req1.set_customer_id("age-72-user");
    req1.set_gender(calc::Gender::MALE);
    req1.set_date_of_birth("1952-05-15");
    req1.set_retirement_date("2024-06-01");
    req1.set_pension_type(calc::PensionType::OLD_AGE);
    req1.set_zp_macroeconomic_average(10000.0);

    auto *p1 = req1.add_employment_history();
    p1->set_start_date("2000-01-01");
    p1->set_end_date("2020-12-31");
    p1->set_multiplier(1.0);

    auto *s1 = req1.add_salary_history();
    s1->set_year(2020);
    s1->set_month(1);
    s1->set_amount(10000.0);

    auto res1 = calculator.calculate(&req1);
    assert(res1.success == true);
    assert(res1.total_benefit_surcharges == 300.0); // +300 UAH for age 70-74

    // Test Case 2: Person age 77 (born 1947-01-10, retiring 2024-06-01) -> +456 UAH
    calc::CalculatePensionRequest req2 = req1;
    req2.set_customer_id("age-77-user");
    req2.set_date_of_birth("1947-01-10");
    auto res2 = calculator.calculate(&req2);
    assert(res2.success == true);
    assert(res2.total_benefit_surcharges == 456.0); // +456 UAH for age 75-79

    // Test Case 3: Person age 82 (born 1942-01-10, retiring 2024-06-01) -> +570 UAH
    calc::CalculatePensionRequest req3 = req1;
    req3.set_customer_id("age-82-user");
    req3.set_date_of_birth("1942-01-10");
    auto res3 = calculator.calculate(&req3);
    assert(res3.success == true);
    assert(res3.total_benefit_surcharges == 570.0); // +570 UAH for age 80+

    // Test Case 4: High income pension exceeding 10,340.35 UAH cap -> +0 UAH age supplement
    calc::CalculatePensionRequest req4 = req1;
    req4.set_customer_id("high-income-age-72-user");
    req4.set_date_of_birth("1952-05-15");
    req4.set_zp_macroeconomic_average(40000.0);
    s1 = req4.mutable_salary_history(0);
    s1->set_amount(80000.0);
    auto res4 = calculator.calculate(&req4);
    assert(res4.success == true);
    assert(res4.total_benefit_surcharges == 0.0); // Capped at 10,340.35 UAH

    std::cout << "  ✓ test_age_based_surcharges passed! (Verified +300, +456, +570 UAH brackets and 10,340.35 UAH cap)" << std::endl;
}

int main()
{
    std::cout << "=========================================" << std::endl;
    std::cout << "Running Ukrainian Pension Calculator Unit Tests" << std::endl;
    std::cout << "=========================================" << std::endl;

    test_solidarity_pension_pipeline_stage1_to_5();
    test_disability_pension_modifiers();
    test_age_based_surcharges();

    std::cout << "=========================================" << std::endl;
    std::cout << "✅ All Ukrainian Pension Calculator Unit Tests Passed!" << std::endl;
    std::cout << "=========================================" << std::endl;
    return 0;
}
