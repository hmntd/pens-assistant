#ifndef LEGAL_BOUNDS_STAGE_H
#define LEGAL_BOUNDS_STAGE_H

#include "i_pension_calculation_stage.h"

namespace calc
{
    namespace service
    {
        class LegalBoundsStage final : public IPensionCalculationStage
        {
        public:
            bool execute(PensionCalculationContext &ctx, std::string &error) const override;
            const char *name() const override { return "LegalBoundsStage"; }
        };
    }
}

#endif // LEGAL_BOUNDS_STAGE_H
