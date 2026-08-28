#ifndef PENSION_TYPE_STAGE_H
#define PENSION_TYPE_STAGE_H

#include "i_pension_calculation_stage.h"

namespace calc
{
    namespace service
    {
        class PensionTypeStage final : public IPensionCalculationStage
        {
        public:
            bool execute(PensionCalculationContext &ctx, std::string &error) const override;
            const char *name() const override { return "PensionTypeStage"; }
        };
    }
}

#endif // PENSION_TYPE_STAGE_H
