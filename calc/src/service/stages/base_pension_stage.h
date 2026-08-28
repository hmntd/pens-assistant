#ifndef BASE_PENSION_STAGE_H
#define BASE_PENSION_STAGE_H

#include "i_pension_calculation_stage.h"

namespace calc
{
    namespace service
    {
        class BasePensionStage final : public IPensionCalculationStage
        {
        public:
            bool execute(PensionCalculationContext &ctx, std::string &error) const override;
            const char *name() const override { return "BasePensionStage(P)"; }
        };
    }
}

#endif // BASE_PENSION_STAGE_H
