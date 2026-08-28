#include "legal_bounds_stage.h"
#include "../util/money_format.h"
#include <sstream>

namespace calc
{
    namespace service
    {
        bool LegalBoundsStage::execute(PensionCalculationContext &ctx, std::string &error) const
        {
            double pre_clamped = (ctx.base_pension * ctx.pension_type_modifier) + ctx.extra_service_allowance + ctx.total_benefit_surcharges;
            ctx.pre_clamped_pension = pre_clamped;

            double min_pension = ctx.limits.for_disabled_persons * 1.0;
            double max_pension = ctx.limits.for_disabled_persons * 10.0;

            double final_pension = pre_clamped;
            bool min_clamped = false;
            bool max_clamped = false;

            if (pre_clamped < min_pension)
            {
                final_pension = min_pension;
                min_clamped = true;
            }
            else if (pre_clamped > max_pension)
            {
                final_pension = max_pension;
                max_clamped = true;
            }

            ctx.final_pension = final_pension;
            ctx.is_minimum_clamped = min_clamped;
            ctx.is_maximum_clamped = max_clamped;

            std::ostringstream ss;
            ss << "Stage 5 [Legal Bounds Clamping Art. 28 Law 1058-IV]: Calculated Pre-Clamped Pension = "
               << util::formatUah(pre_clamped) << " UAH. Statutory Limits: Min = "
               << util::formatUah(min_pension) << " UAH, Max = " << util::formatUah(max_pension) << " UAH.";

            if (min_clamped)
            {
                ss << " Applied MINIMUM PENSION CLAMP -> Final Pension = " << util::formatUah(final_pension) << " UAH.";
            }
            else if (max_clamped)
            {
                ss << " Applied MAXIMUM PENSION CLAMP -> Final Pension = " << util::formatUah(final_pension) << " UAH.";
            }
            else
            {
                ss << " Final Pension = " << util::formatUah(final_pension) << " UAH.";
            }

            ctx.logs.push_back(ss.str());
            return true;
        }
    }
}
