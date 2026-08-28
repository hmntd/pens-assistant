#ifndef BENEFIT_SURCHARGE_STAGE_H
#define BENEFIT_SURCHARGE_STAGE_H

#include "i_pension_calculation_stage.h"
#include "../pension_models.h"

namespace calc
{
    namespace service
    {
        class BenefitSurchargeStage final : public IPensionCalculationStage
        {
        public:
            explicit BenefitSurchargeStage(const BenefitRulesEngine &engine) : engine_(engine) {}

            bool execute(PensionCalculationContext &ctx, std::string &error) const override;
            const char *name() const override { return "BenefitSurchargeStage"; }

        private:
            const BenefitRulesEngine &engine_;
        };
    }
}

#endif // BENEFIT_SURCHARGE_STAGE_H
