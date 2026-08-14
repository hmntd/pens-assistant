#ifndef COEFFICIENT_REPOSITORY_H
#define COEFFICIENT_REPOSITORY_H

#include <string>
#include <vector>
#include <optional>

#include "../service/pension_models.h"

namespace calc
{
    namespace repository
    {

        struct CoefficientRecord
        {
            int id{0};
            int year{0};
            int month{0};
            double coefficient{1.0};
            std::string description;
        };

        struct SubsistenceMinimumRecord
        {
            int id{0};
            int year{0};
            double for_disabled_persons{0.0};
            double general_minimum{0.0};
        };

        class CoefficientRepository
        {
        public:
            double getCoefficient(int year, int month);
            std::vector<CoefficientRecord> listAll();
            std::optional<CoefficientRecord> add(int year, int month, double coefficient, const std::string &description);
            std::optional<CoefficientRecord> update(int id, int year, int month, double coefficient, const std::string &description);
            bool remove(int id);
            double getAverageSalary(int year, int month) const;
            bool upsertAverageSalary(int year, int month, double amount);
            service::SubsistenceLimits getSubsistenceLimits(int year) const;
            bool upsertSubsistenceLimits(int year, double for_disabled, double general);
            std::vector<SubsistenceMinimumRecord> listSubsistenceMinimums() const;
            std::optional<SubsistenceMinimumRecord> updateSubsistenceMinimum(int id, int year, double for_disabled, double general);
            bool deleteSubsistenceMinimum(int id);
        };

    }
}

#endif
