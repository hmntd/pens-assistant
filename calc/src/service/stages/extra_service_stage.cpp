#include "extra_service_stage.h"
#include "../util/money_format.h"
#include <algorithm>
#include <sstream>

namespace calc
{
    namespace service
    {
        bool ExtraServiceStage::execute(PensionCalculationContext &ctx, std::string &error) const
        {
            int extra_years = ctx.overtime_service_months / 12;
            double allowance = 0.0;

            if (extra_years > 0)
            {
                double modified_base = ctx.base_pension * ctx.pension_type_modifier;
                double cap_base = std::min(modified_base, ctx.limits.for_disabled_persons);
                allowance = extra_years * 0.01 * cap_base;
            }

            ctx.extra_service_allowance = allowance;

            std::ostringstream ss;
            if (extra_years > 0)
            {
                ss << "Stage 3 [Extra Service Allowance Art. 28 Law 1058-IV]: Added "
                   << util::formatUah(allowance) << " UAH allowance for " << extra_years
                   << " overtime service years (1% per year above required threshold).";
            }
            else
            {
                ss << "Stage 3 [Extra Service Allowance Art. 28 Law 1058-IV]: No overtime service years recorded (Allowance = 0.00 UAH).";
            }
            ctx.logs.push_back(ss.str());

            return true;
        }
    }
}
