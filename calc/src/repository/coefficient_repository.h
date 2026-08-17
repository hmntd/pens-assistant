#ifndef COEFFICIENT_REPOSITORY_H
#define COEFFICIENT_REPOSITORY_H

#include <string>
#include <vector>
#include <optional>
#include <map>
#include <utility>

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
            double age_surcharge_cap{0.0};
        };

        class CoefficientRepository
        {
        private:
            bool mock_mode_{false};
            static inline std::map<std::pair<int, int>, double> mock_salaries_{};
            static inline std::map<int, service::SubsistenceLimits> mock_limits_{};
            static inline std::map<std::pair<int, int>, double> mock_coefficients_{};

        public:
            explicit CoefficientRepository(bool mock_mode = false) : mock_mode_(mock_mode) {}
            void setMockMode(bool enable) { mock_mode_ = enable; }
            bool isMockMode() const { return mock_mode_; }

            double getCoefficient(int year, int month);
            std::vector<CoefficientRecord> listAll();
            std::optional<CoefficientRecord> add(int year, int month, double coefficient, const std::string &description);
            std::optional<CoefficientRecord> update(int id, int year, int month, double coefficient, const std::string &description);
            bool remove(int id);
            double getAverageSalary(int year, int month) const;
            bool upsertAverageSalary(int year, int month, double amount);
            service::SubsistenceLimits getSubsistenceLimits(int year) const;
            bool upsertSubsistenceLimits(int year, double for_disabled, double general, double age_surcharge_cap = 10340.35);
            std::vector<SubsistenceMinimumRecord> listSubsistenceMinimums() const;
            std::optional<SubsistenceMinimumRecord> updateSubsistenceMinimum(int id, int year, double for_disabled, double general, double age_surcharge_cap = 10340.35);
            bool deleteSubsistenceMinimum(int id);
        };

    }
}

#endif
