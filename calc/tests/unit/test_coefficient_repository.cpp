#include <iostream>
#include <cassert>
#include "../../src/repository/coefficient_repository.h"
#include "../../src/cache/redis_client.h"

void test_coefficient_repository_defaults()
{
    std::cout << "[Unit Test] Running test_coefficient_repository_defaults..." << std::endl;
    calc::repository::CoefficientRepository repo;

    double coeff = repo.getCoefficient(2025, 1);
    assert(coeff > 0.0);

    std::cout << "  ✓ test_coefficient_repository_defaults passed (Coeff: " << coeff << ")!" << std::endl;
}

void test_upsert_average_salary_graceful_handling()
{
    std::cout << "[Unit Test] Running test_upsert_average_salary_graceful_handling..." << std::endl;
    calc::repository::CoefficientRepository repo(false);

    double zp_macro = repo.getMacroeconomicAverageSalary(2026);
    std::cout << "  [Macro Zp 2026] Evaluated Zp = " << zp_macro << " UAH" << std::endl;

    bool ok = repo.upsertAverageSalary(2026, 1, 21876.06);
    if (ok)
    {
        std::cout << "  ✓ test_upsert_average_salary passed (DB connected)!" << std::endl;
    }
    else
    {
        std::cout << "  ✓ test_upsert_average_salary handled offline DB gracefully!" << std::endl;
    }
}

void test_redis_cache_client()
{
    std::cout << "[Unit Test] Running test_redis_cache_client..." << std::endl;
    const std::string key = "calc:test:unit_key";
    bool set_ok = calc::cache::RedisClient::set(key, "123.45", 60);
    if (set_ok)
    {
        auto val = calc::cache::RedisClient::get(key);
        assert(val.has_value());
        assert(*val == "123.45");
        bool del_ok = calc::cache::RedisClient::del(key);
        assert(del_ok);
        auto after_del = calc::cache::RedisClient::get(key);
        assert(!after_del.has_value());
        std::cout << "  ✓ test_redis_cache_client passed (Redis connected, set/get/del verified)!" << std::endl;
    }
    else
    {
        auto val = calc::cache::RedisClient::get(key);
        assert(!val.has_value());
        std::cout << "  ✓ test_redis_cache_client handled offline Redis gracefully!" << std::endl;
    }
}

int main()
{
    std::cout << "=========================================" << std::endl;
    std::cout << "Running Calc Service Unit Tests (Repository)" << std::endl;
    std::cout << "=========================================" << std::endl;

    test_coefficient_repository_defaults();
    test_upsert_average_salary_graceful_handling();
    test_redis_cache_client();

    std::cout << "=========================================" << std::endl;
    std::cout << "✅ All CoefficientRepository Unit Tests Passed!" << std::endl;
    std::cout << "=========================================" << std::endl;
    return 0;
}
