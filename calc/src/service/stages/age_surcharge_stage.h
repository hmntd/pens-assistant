#ifndef AGE_SURCHARGE_STAGE_H
#define AGE_SURCHARGE_STAGE_H

#include "i_pension_calculation_stage.h"

namespace calc
{
    namespace service
    {
        class AgeSurchargeStage final : public IPensionCalculationStage
        {
        public:
            bool execute(PensionCalculationContext &ctx, std::string &error) const override;
            const char *name() const override { return "AgeSurchargeStage"; }
        };
    }
}

#endif // AGE_SURCHARGE_STAGE_H
