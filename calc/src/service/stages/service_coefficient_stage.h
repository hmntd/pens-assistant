#ifndef SERVICE_COEFFICIENT_STAGE_H
#define SERVICE_COEFFICIENT_STAGE_H

#include "i_pension_calculation_stage.h"

namespace calc
{
    namespace service
    {
        class ServiceCoefficientStage final : public IPensionCalculationStage
        {
        public:
            bool execute(PensionCalculationContext &ctx, std::string &error) const override;
            const char *name() const override { return "ServiceCoefficientStage(Ks)"; }
        };
    }
}

#endif // SERVICE_COEFFICIENT_STAGE_H
