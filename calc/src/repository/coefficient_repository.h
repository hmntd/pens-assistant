#ifndef COEFFICIENT_REPOSITORY_H
#define COEFFICIENT_REPOSITORY_H

#include <string>
#include <vector>
#include <optional>

namespace calc {
namespace repository {

struct CoefficientRecord {
    int id{0};
    int year{0};
    int month{0};
    double coefficient{1.0};
    std::string description;
};

class CoefficientRepository {
public:
    double getCoefficient(int year, int month);
    std::vector<CoefficientRecord> listAll();
    std::optional<CoefficientRecord> add(int year, int month, double coefficient, const std::string& description);
    std::optional<CoefficientRecord> update(int id, int year, int month, double coefficient, const std::string& description);
    bool remove(int id);
};

}
}

#endif
