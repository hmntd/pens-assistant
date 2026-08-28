#ifndef I_PENSION_CALCULATION_STAGE_H
#define I_PENSION_CALCULATION_STAGE_H

#include "../context/pension_calculation_context.h"
#include <string>

namespace calc
{
    namespace service
    {
        class IPensionCalculationStage
        {
        public:
            virtual ~IPensionCalculationStage() = default;

            virtual bool execute(PensionCalculationContext &ctx, std::string &error) const = 0;
            virtual const char *name() const = 0;
        };
    }
}

#endif // I_PENSION_CALCULATION_STAGE_H
