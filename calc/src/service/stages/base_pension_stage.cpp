#include "base_pension_stage.h"
#include "../util/money_format.h"
#include <sstream>

namespace calc
{
    namespace service
    {
        bool BasePensionStage::execute(PensionCalculationContext &ctx, std::string &error) const
        {
            double base_pension = ctx.zp_macroeconomic_average * ctx.kz_wage_coefficient * ctx.ks_service_coefficient;
            ctx.base_pension = base_pension;

            std::ostringstream ss;
            ss << "Stage 1c [Base Solidarity Pension P = Zp * Kz * Ks]: Zp = "
               << util::formatUah(ctx.zp_macroeconomic_average) << " UAH, Kz = "
               << util::formatCoefficient(ctx.kz_wage_coefficient) << ", Ks = "
               << util::formatCoefficient(ctx.ks_service_coefficient) << ". Base Pension P = "
               << util::formatUah(base_pension) << " UAH.";
            ctx.logs.push_back(ss.str());

            return true;
        }
    }
}
