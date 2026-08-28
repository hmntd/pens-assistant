#ifndef EXTRA_SERVICE_STAGE_H
#define EXTRA_SERVICE_STAGE_H

#include "i_pension_calculation_stage.h"

namespace calc
{
    namespace service
    {
        class ExtraServiceStage final : public IPensionCalculationStage
        {
        public:
            bool execute(PensionCalculationContext &ctx, std::string &error) const override;
            const char *name() const override { return "ExtraServiceStage"; }
        };
    }
}

#endif // EXTRA_SERVICE_STAGE_H
