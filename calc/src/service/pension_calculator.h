#ifndef PENSION_CALCULATOR_H
#define PENSION_CALCULATOR_H

#include "../repository/coefficient_repository.h"
#include "pension_models.h"
#include "calc.pb.h"
#include <string>
#include <vector>

namespace calc
{
    namespace service
    {

        struct CalculationResult
        {
            bool success{false};
            double final_pension{0.0};
            double base_pension{0.0};
            double zp_macroeconomic_average{0.0};
            double kz_wage_coefficient{0.0};
            double ks_service_coefficient{0.0};
            int total_service_months{0};
            double pension_type_modifier{1.0};
            double extra_service_allowance{0.0};
            double total_benefit_surcharges{0.0};
            double pre_clamped_pension{0.0};
            bool is_minimum_clamped{false};
            bool is_maximum_clamped{false};
            std::vector<SurchargeResult> applied_benefits;
            std::vector<std::string> calculation_logs;
            std::string error_message;

            // Legacy compatibility helpers
            double estimated_monthly_pension{0.0};
            double total_accumulated_capital{0.0};
            std::string breakdown;
        };

        class PensionCalculator
        {
        private:
            repository::CoefficientRepository repo_;
            BenefitRulesEngine benefit_engine_;

            int calculateTotalServiceMonths(const calc::CalculatePensionRequest *request, std::vector<std::string> &logs, std::string &error_message) const;
            double calculateWageCoefficientKz(const calc::CalculatePensionRequest *request, std::vector<std::string> &logs, std::string &error_message) const;
            double calculatePensionTypeModifier(const calc::CalculatePensionRequest *request, std::vector<std::string> &logs, std::string &error_message) const;
            double calculateExtraServiceAllowance(const calc::CalculatePensionRequest *request, int total_months, double base_pension, const SubsistenceLimits &limits, std::vector<std::string> &logs) const;
            int calculateAgeInYears(const std::string &date_of_birth, const std::string &retirement_date) const;
            double calculateAgeSurcharge(const calc::CalculatePensionRequest *request, double pre_age_pension, const SubsistenceLimits &limits, std::vector<std::string> &logs, SurchargeResult &out_surcharge) const;

        public:
            explicit PensionCalculator(repository::CoefficientRepository repo = repository::CoefficientRepository());

            CalculationResult calculate(const calc::CalculatePensionRequest *request);
            CalculationResult calculateLegacy(const calc::PensionRequest *request);
        };

    }
}

#endif
