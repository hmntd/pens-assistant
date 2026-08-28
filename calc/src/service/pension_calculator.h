#ifndef PENSION_CALCULATOR_H
#define PENSION_CALCULATOR_H

#include "../repository/coefficient_repository.h"
#include "pension_models.h"
#include "context/pension_calculation_context.h"
#include "stages/i_pension_calculation_stage.h"
#include "util/i_clock.h"
#include "util/i_logger.h"
#include "adapters/legacy_request_adapter.h"
#include "calc.pb.h"
#include <memory>
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
            bool is_hypothetical{false};
            bool criteria_met{false};
            std::string hypothetical_disclaimer;

            // Legacy compatibility helpers
            double estimated_monthly_pension{0.0};
            double total_accumulated_capital{0.0};
            std::string breakdown;
        };

        class PensionCalculator
        {
        public:
            explicit PensionCalculator(repository::CoefficientRepository repo = repository::CoefficientRepository());

            PensionCalculator(repository::CoefficientRepository repo,
                               std::unique_ptr<IClock> clock,
                               std::unique_ptr<ILogger> logger,
                               std::vector<std::unique_ptr<IPensionCalculationStage>> stages);

            CalculationResult calculate(const calc::CalculatePensionRequest *request);
            CalculationResult calculateLegacy(const calc::PensionRequest *request);

        private:
            repository::CoefficientRepository repo_;
            BenefitRulesEngine benefit_engine_;
            std::unique_ptr<IClock> clock_;
            std::unique_ptr<ILogger> logger_;
            std::vector<std::unique_ptr<IPensionCalculationStage>> stages_;
            LegacyRequestAdapter legacy_adapter_;

            bool resolveRetirementYear(const calc::CalculatePensionRequest *request,
                                        int &retirement_year, bool &is_hypothetical_mode,
                                        int &requested_retirement_year, std::string &error) const;
        };
    }
}

#endif // PENSION_CALCULATOR_H
