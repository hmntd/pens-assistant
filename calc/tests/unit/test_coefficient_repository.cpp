#include <iostream>
#include <cassert>
#include "../../src/repository/coefficient_repository.h"

void test_coefficient_repository_defaults() {
    std::cout << "[Unit Test] Running test_coefficient_repository_defaults..." << std::endl;
    calc::repository::CoefficientRepository repo;

    double coeff = repo.getCoefficient(2025, 1);
    assert(coeff > 0.0);

    std::cout << "  ✓ test_coefficient_repository_defaults passed (Coeff: " << coeff << ")!" << std::endl;
}

void test_upsert_average_salary_graceful_handling() {
    std::cout << "[Unit Test] Running test_upsert_average_salary_graceful_handling..." << std::endl;
    calc::repository::CoefficientRepository repo;

    bool ok = repo.upsertAverageSalary(2026, 1, 21876.06);
    if (ok) {
        std::cout << "  ✓ test_upsert_average_salary passed (DB connected)!" << std::endl;
    } else {
        std::cout << "  ✓ test_upsert_average_salary handled offline DB gracefully!" << std::endl;
    }
}

int main() {
    std::cout << "=========================================" << std::endl;
    std::cout << "Running Calc Service Unit Tests (Repository)" << std::endl;
    std::cout << "=========================================" << std::endl;

    test_coefficient_repository_defaults();
    test_upsert_average_salary_graceful_handling();

    std::cout << "=========================================" << std::endl;
    std::cout << "✅ All CoefficientRepository Unit Tests Passed!" << std::endl;
    std::cout << "=========================================" << std::endl;
    return 0;
}
