#include "pension_type_stage.h"
#include <sstream>

namespace calc
{
    namespace service
    {
        bool PensionTypeStage::execute(PensionCalculationContext &ctx, std::string &error) const
        {
            const auto *request = ctx.request;
            double modifier = 1.0;
            std::string log_desc;

            switch (request->pension_type())
            {
            case calc::PensionType::OLD_AGE:
                modifier = 1.0;
                log_desc = "Old Age Pension (100% standard rate)";
                break;

            case calc::PensionType::DISABILITY:
                switch (request->disability_group())
                {
                case calc::DisabilityGroup::GROUP_1:
                    modifier = 1.00;
                    log_desc = "Disability Group 1 (100% of Base Pension)";
                    break;
                case calc::DisabilityGroup::GROUP_2:
                    modifier = 0.90;
                    log_desc = "Disability Group 2 (90% of Base Pension)";
                    break;
                case calc::DisabilityGroup::GROUP_3:
                    modifier = 0.50;
                    log_desc = "Disability Group 3 (50% of Base Pension)";
                    break;
                default:
                    modifier = 1.00;
                    log_desc = "Disability Pension (Default Group 1 100%)";
                    break;
                }
                break;

            case calc::PensionType::LOSS_OF_BREADWINNER:
                if (request->dependents_count() >= 2)
                {
                    modifier = 1.00;
                    log_desc = "Survivor Pension (2+ Dependents: 100% of Base Pension)";
                }
                else
                {
                    modifier = 0.50;
                    log_desc = "Survivor Pension (1 Dependent: 50% of Base Pension)";
                }
                break;

            default:
                modifier = 1.0;
                log_desc = "Standard Solidarity Pension";
                break;
            }

            ctx.pension_type_modifier = modifier;

            std::ostringstream ss;
            ss << "Stage 2 [Pension Type Modifier]: Applied " << log_desc << " (Modifier = " << modifier << ").";
            ctx.logs.push_back(ss.str());

            return true;
        }
    }
}
