#include "age_surcharge_stage.h"
#include "../util/date_utils.h"
#include "../util/money_format.h"
#include <sstream>

namespace calc
{
    namespace service
    {
        bool AgeSurchargeStage::execute(PensionCalculationContext &ctx, std::string &error) const
        {
            const auto *request = ctx.request;
            int client_age = util::ageInYears(request->date_of_birth(), request->retirement_date());

            double pre_age_pension = (ctx.base_pension * ctx.pension_type_modifier) + ctx.extra_service_allowance + ctx.total_benefit_surcharges;
            double surcharge_amount = 0.0;
            std::string bracket_name;

            if (pre_age_pension < ctx.limits.age_surcharge_cap)
            {
                if (client_age >= 80)
                {
                    surcharge_amount = 570.0;
                    bracket_name = "Age Supplement 80+ years (+570 UAH)";
                }
                else if (client_age >= 75)
                {
                    surcharge_amount = 456.0;
                    bracket_name = "Age Supplement 75-79 years (+456 UAH)";
                }
                else if (client_age >= 70)
                {
                    surcharge_amount = 300.0;
                    bracket_name = "Age Supplement 70-74 years (+300 UAH)";
                }

                if (pre_age_pension + surcharge_amount > ctx.limits.age_surcharge_cap)
                {
                    surcharge_amount = ctx.limits.age_surcharge_cap - pre_age_pension;
                }
            }

            if (surcharge_amount > 0.0)
            {
                ctx.age_surcharge = surcharge_amount;
                ctx.total_benefit_surcharges += surcharge_amount;

                SurchargeResult sr;
                sr.type = calc::BenefitType::AGE_SUPPLEMENT;
                sr.name = bracket_name;
                sr.amount = surcharge_amount;
                ctx.applied_benefits.push_back(sr);

                std::ostringstream ss;
                ss << "Stage 4b [Age Surcharge Law 1058-IV]: Client age " << client_age << " years -> Applied "
                   << bracket_name << " (+" << util::formatUah(surcharge_amount) << " UAH).";
                ctx.logs.push_back(ss.str());
            }

            return true;
        }
    }
}
