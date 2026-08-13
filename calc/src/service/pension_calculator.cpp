#include "pension_calculator.h"
#include <iostream>

namespace calc {
namespace service {

PensionCalculator::PensionCalculator(repository::CoefficientRepository repo)
    : repo_(std::move(repo)) {}

CalculationResult PensionCalculator::calculate(const calc::PensionRequest* request) {
    CalculationResult res;
    std::cout << "[Calc Service] Calculating pension for customer: " << request->customer_id() << std::endl;

    double adjusted_total_income = 0.0;
    double raw_total_income = 0.0;
    int total_months = 0;
    int coefficients_applied = 0;

    for (const auto& record : request->history()) {
        double income = record.annual_income();
        raw_total_income += income;
        total_months += record.months_worked();

        double coef = repo_.getCoefficient(record.year(), 1);
        adjusted_total_income += (income * coef);
        if (coef != 1.0) {
            coefficients_applied++;
        }
    }

    double estimated_monthly = (adjusted_total_income * 0.02) / 12.0;
    if (estimated_monthly < 500.0) {
        estimated_monthly = 1250.0;
    }

    res.success = true;
    res.estimated_monthly_pension = estimated_monthly;
    res.total_accumulated_capital = adjusted_total_income * 0.35;
    res.breakdown = "Processed " + std::to_string(request->history_size()) +
                    " tax records. Total months worked: " + std::to_string(total_months) +
                    ". Index coefficients applied from PostgreSQL calc_db: " + std::to_string(coefficients_applied);
    res.error_message = "";

    return res;
}

}
}
