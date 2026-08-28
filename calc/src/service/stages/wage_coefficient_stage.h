#ifndef WAGE_COEFFICIENT_STAGE_H
#define WAGE_COEFFICIENT_STAGE_H

#include "i_pension_calculation_stage.h"
#include "../../repository/coefficient_repository.h"

namespace calc
{
    namespace service
    {
        class WageCoefficientStage final : public IPensionCalculationStage
        {
        public:
            explicit WageCoefficientStage(const repository::CoefficientRepository &repo) : repo_(repo) {}

            bool execute(PensionCalculationContext &ctx, std::string &error) const override;
            const char *name() const override { return "WageCoefficientStage(Kz)"; }

        private:
            const repository::CoefficientRepository &repo_;
        };
    }
}

#endif // WAGE_COEFFICIENT_STAGE_H
