#ifndef PENSION_CALCULATOR_H
#define PENSION_CALCULATOR_H

#include "../repository/coefficient_repository.h"
#include "calc.pb.h"
#include <string>

namespace calc {
namespace service {

struct CalculationResult {
    bool success{false};
    double estimated_monthly_pension{0.0};
    double total_accumulated_capital{0.0};
    std::string breakdown;
    std::string error_message;
};

class PensionCalculator {
private:
    repository::CoefficientRepository repo_;

public:
    explicit PensionCalculator(repository::CoefficientRepository repo = repository::CoefficientRepository());
    CalculationResult calculate(const calc::PensionRequest* request);
};

}
}

#endif
