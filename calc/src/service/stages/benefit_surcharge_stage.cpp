#include "benefit_surcharge_stage.h"
#include "../util/money_format.h"
#include <sstream>

namespace calc
{
    namespace service
    {
        bool BenefitSurchargeStage::execute(PensionCalculationContext &ctx, std::string &error) const
        {
            const auto *request = ctx.request;
            std::vector<calc::BenefitType> active_benefits;
            for (int i = 0; i < request->benefits_size(); ++i)
            {
                active_benefits.push_back(request->benefits(i));
            }

            auto surcharges = engine_.evaluateBenefits(active_benefits, ctx.limits);
            double total_surcharges = 0.0;

            for (const auto &s : surcharges)
            {
                total_surcharges += s.amount;
                ctx.applied_benefits.push_back(s);

                std::ostringstream ss;
                ss << "Stage 4a [Status Benefit Surcharge]: Applied " << s.name << " (+"
                   << util::formatUah(s.amount) << " UAH).";
                ctx.logs.push_back(ss.str());
            }

            ctx.total_benefit_surcharges += total_surcharges;
            return true;
        }
    }
}
