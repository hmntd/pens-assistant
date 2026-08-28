#ifndef PENSION_CALCULATION_CONTEXT_H
#define PENSION_CALCULATION_CONTEXT_H

#include "../pension_models.h"
#include "calc.pb.h"
#include <string>
#include <vector>

namespace calc
{
    namespace service
    {
        struct PensionCalculationContext
        {
            const calc::CalculatePensionRequest *request{nullptr};
            int current_year{0};
            int retirement_year{0};
            bool is_hypothetical_mode{false};

            SubsistenceLimits limits;
            double zp_macroeconomic_average{0.0};

            int total_service_months{0};
            int overtime_service_months{0};
            double ks_service_coefficient{0.0};

            double kz_wage_coefficient{1.0};
            double base_pension{0.0};
            double pension_type_modifier{1.0};
            double extra_service_allowance{0.0};

            double total_benefit_surcharges{0.0};
            double age_surcharge{0.0};

            double pre_clamped_pension{0.0};
            double final_pension{0.0};

            bool is_minimum_clamped{false};
            bool is_maximum_clamped{false};

            std::vector<SurchargeResult> applied_benefits;
            std::vector<std::string> logs;
        };
    }
}

#endif // PENSION_CALCULATION_CONTEXT_H
