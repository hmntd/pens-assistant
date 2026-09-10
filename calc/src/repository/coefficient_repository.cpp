#include "coefficient_repository.h"
#include "../db/db_config.h"
#include <pqxx/pqxx>
#include <iostream>

namespace calc
{
    namespace repository
    {

        CoefficientRepository::CoefficientRepository(bool mock_mode)
            : mock_mode_(mock_mode)
        {
            if (mock_mode_)
            {
                initMockData();
            }
        }

        void CoefficientRepository::initMockData()
        {
            if (mock_limits_.empty())
            {
                mock_limits_[2023] = service::SubsistenceLimits{2093.0, 2589.0, 10340.35, 300.0, 456.0, 570.0};
                mock_limits_[2024] = service::SubsistenceLimits{2361.0, 2920.0, 10340.35, 300.0, 456.0, 570.0};
                mock_limits_[2025] = service::SubsistenceLimits{2361.0, 2920.0, 10340.35, 300.0, 456.0, 570.0};
                mock_limits_[2026] = service::SubsistenceLimits{2361.0, 2920.0, 10340.35, 300.0, 456.0, 570.0};
            }
            if (mock_salaries_.empty())
            {
                mock_salaries_[{2024, 0}] = 13559.41;
                mock_salaries_[{2025, 0}] = 15000.00;
                mock_salaries_[{2026, 0}] = 17487.10;
            }
        }

        double CoefficientRepository::getCoefficient(int year, int month)
        {
            if (mock_mode_)
            {
                auto it = mock_coefficients_.find({year, month});
                if (it != mock_coefficients_.end())
                {
                    return it->second;
                }
                return 1.0;
            }

            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    std::cerr << "[Calc DB] Failed to open PostgreSQL connection." << std::endl;
                    return 1.0;
                }

                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params(
                    "SELECT coefficient FROM pension_coefficients WHERE year = $1 AND month = $2 LIMIT 1",
                    year, month);
                txn.commit();

                if (!res.empty())
                {
                    double coef = res[0][0].as<double>();
                    std::cout << "[Calc DB] Found coefficient for " << year << "-" << month << ": " << coef << std::endl;
                    return coef;
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
            }

            return 1.0;
        }

        std::vector<CoefficientRecord> CoefficientRepository::listAll()
        {
            std::vector<CoefficientRecord> results;
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                pqxx::work txn(conn);
                pqxx::result res = txn.exec(
                    "SELECT id, year, month, coefficient, COALESCE(description, '') FROM pension_coefficients ORDER BY year DESC, month DESC");
                txn.commit();

                for (const auto &row : res)
                {
                    CoefficientRecord rec;
                    rec.id = row[0].as<int>();
                    rec.year = row[1].as<int>();
                    rec.month = row[2].as<int>();
                    rec.coefficient = row[3].as<double>();
                    rec.description = row[4].as<std::string>();
                    results.push_back(rec);
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
            }
            return results;
        }

        std::optional<CoefficientRecord> CoefficientRepository::add(int year, int month, double coefficient, const std::string &description)
        {
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params(
                    "INSERT INTO pension_coefficients (year, month, coefficient, description) VALUES ($1, $2, $3, $4) ON CONFLICT (year, month) DO UPDATE SET coefficient = EXCLUDED.coefficient, description = EXCLUDED.description RETURNING id, year, month, coefficient, COALESCE(description, '')",
                    year, month, coefficient, description);
                txn.commit();

                if (!res.empty())
                {
                    CoefficientRecord rec;
                    rec.id = res[0][0].as<int>();
                    rec.year = res[0][1].as<int>();
                    rec.month = res[0][2].as<int>();
                    rec.coefficient = res[0][3].as<double>();
                    rec.description = res[0][4].as<std::string>();
                    return rec;
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
            }
            return std::nullopt;
        }

        std::optional<CoefficientRecord> CoefficientRepository::update(int id, int year, int month, double coefficient, const std::string &description)
        {
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params(
                    "UPDATE pension_coefficients SET year = $1, month = $2, coefficient = $3, description = $4, updated_at = CURRENT_TIMESTAMP WHERE id = $5 RETURNING id, year, month, coefficient, COALESCE(description, '')",
                    year, month, coefficient, description, id);
                txn.commit();

                if (!res.empty())
                {
                    CoefficientRecord rec;
                    rec.id = res[0][0].as<int>();
                    rec.year = res[0][1].as<int>();
                    rec.month = res[0][2].as<int>();
                    rec.coefficient = res[0][3].as<double>();
                    rec.description = res[0][4].as<std::string>();
                    return rec;
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
            }
            return std::nullopt;
        }

        bool CoefficientRepository::remove(int id)
        {
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params("DELETE FROM pension_coefficients WHERE id = $1", id);
                txn.commit();
                return res.affected_rows() > 0;
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
            }
            return false;
        }

        double CoefficientRepository::getAverageSalary(int year, int month) const
        {
            if (mock_mode_)
            {
                auto it = mock_salaries_.find({year, month});
                if (it != mock_salaries_.end())
                {
                    return it->second;
                }
                auto it_year = mock_salaries_.find({year, 0});
                if (it_year != mock_salaries_.end())
                {
                    return it_year->second;
                }
                for (const auto &pair : mock_salaries_)
                {
                    if (pair.second > 0.0)
                    {
                        return pair.second;
                    }
                }
                return 0.0;
            }

            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    return 0.0;
                }

                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params(
                    "SELECT amount FROM pfu_average_salaries WHERE year = $1 AND month = $2",
                    year, month);
                if (!res.empty() && !res[0][0].is_null())
                {
                    double amount = res[0][0].as<double>();
                    if (amount > 0.0)
                        return amount;
                }

                if (month != 0)
                {
                    pqxx::result res_year = txn.exec_params(
                        "SELECT amount FROM pfu_average_salaries WHERE year = $1 AND month = 0",
                        year);
                    if (!res_year.empty() && !res_year[0][0].is_null())
                    {
                        double amount = res_year[0][0].as<double>();
                        if (amount > 0.0)
                            return amount;
                    }
                }

                pqxx::result res_latest = txn.exec(
                    "SELECT amount FROM pfu_average_salaries ORDER BY year DESC, month DESC LIMIT 1");
                if (!res_latest.empty() && !res_latest[0][0].is_null())
                {
                    double latest_amount = res_latest[0][0].as<double>();
                    if (latest_amount > 0.0)
                        return latest_amount;
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception getAverageSalary] " << e.what() << std::endl;
            }
            return 0.0;
        }

        double CoefficientRepository::getMacroeconomicAverageSalary(int retirement_year) const
        {
            if (mock_mode_)
            {
                auto it = mock_salaries_.find({retirement_year, 0});
                if (it != mock_salaries_.end())
                {
                    return it->second;
                }
                for (const auto &pair : mock_salaries_)
                {
                    if (pair.second > 0.0)
                    {
                        return pair.second;
                    }
                }
                return 0.0;
            }

            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    return 0.0;
                }

                pqxx::work txn(conn);

                int target_year = retirement_year;
                time_t t = time(nullptr);
                struct tm tm_now;
#ifdef _WIN32
                localtime_s(&tm_now, &t);
#else
                localtime_r(&t, &tm_now);
#endif
                int current_sys_year = tm_now.tm_year + 1900;

                if (target_year < 2000 || target_year > current_sys_year)
                {
                    target_year = current_sys_year;
                }

                int start_year = target_year - 3;
                int end_year = target_year - 1;

                pqxx::result res = txn.exec_params(
                    "SELECT AVG(amount) FROM pfu_average_salaries WHERE year >= $1 AND year <= $2",
                    start_year, end_year);

                if (!res.empty() && !res[0][0].is_null())
                {
                    double avg = res[0][0].as<double>();
                    if (avg > 0.0)
                        return avg;
                }

                pqxx::result res_recent = txn.exec(
                    "SELECT AVG(amount) FROM pfu_average_salaries WHERE year IN (SELECT DISTINCT year FROM pfu_average_salaries ORDER BY year DESC LIMIT 3)");
                if (!res_recent.empty() && !res_recent[0][0].is_null())
                {
                    double avg_recent = res_recent[0][0].as<double>();
                    if (avg_recent > 0.0)
                        return avg_recent;
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception getMacroeconomicAverageSalary] " << e.what() << std::endl;
            }
            return 0.0;
        }

        std::vector<AverageSalaryData> CoefficientRepository::getAverageSalariesForYears(const std::vector<int> &years) const
        {
            std::vector<AverageSalaryData> list;
            if (mock_mode_)
            {
                for (const auto &pair : mock_salaries_)
                {
                    if (years.empty() || std::find(years.begin(), years.end(), pair.first.first) != years.end())
                    {
                        list.push_back({pair.first.first, pair.first.second, pair.second});
                    }
                }
                return list;
            }

            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                    return list;

                pqxx::work txn(conn);
                pqxx::result res;

                if (!years.empty())
                {
                    std::string query = "SELECT year, month, amount FROM pfu_average_salaries WHERE year IN (";
                    for (size_t i = 0; i < years.size(); ++i)
                    {
                        if (i > 0)
                            query += ",";
                        query += std::to_string(years[i]);
                    }
                    query += ") ORDER BY year ASC, month ASC";
                    res = txn.exec(query);
                }
                else
                {
                    res = txn.exec("SELECT year, month, amount FROM pfu_average_salaries ORDER BY year ASC, month ASC");
                }

                for (const auto &row : res)
                {
                    list.push_back({row[0].as<int>(), row[1].as<int>(), row[2].as<double>()});
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception getAverageSalariesForYears] " << e.what() << std::endl;
            }
            return list;
        }

        bool CoefficientRepository::upsertAverageSalary(int year, int month, double amount)
        {
            mock_salaries_[{year, month}] = amount;
            if (mock_mode_)
            {
                return true;
            }

            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    std::cerr << "[Calc DB] Failed to open PostgreSQL connection." << std::endl;
                    return false;
                }

                pqxx::work txn(conn);
                txn.exec_params(
                    "INSERT INTO pfu_average_salaries (year, month, amount) VALUES ($1, $2, $3) ON CONFLICT (year, month) DO UPDATE SET amount = EXCLUDED.amount",
                    year, month, amount);
                txn.commit();
                return true;
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception upsertAverageSalary] " << e.what() << std::endl;
            }
            return false;
        }

        service::SubsistenceLimits CoefficientRepository::getSubsistenceLimits(int year) const
        {
            if (mock_mode_)
            {
                if (!mock_limits_.empty())
                {
                    auto it = mock_limits_.find(year);
                    if (it != mock_limits_.end())
                    {
                        return it->second;
                    }
                    double min_diff = 1e9;
                    service::SubsistenceLimits best_limits{0.0, 0.0, 0.0, 0.0, 0.0, 0.0};
                    for (const auto &pair : mock_limits_)
                    {
                        double diff = std::abs(pair.first - year);
                        if (diff < min_diff)
                        {
                            min_diff = diff;
                            best_limits = pair.second;
                        }
                    }
                    return best_limits;
                }
                return service::SubsistenceLimits{0.0, 0.0, 0.0, 0.0, 0.0, 0.0};
            }

            service::SubsistenceLimits limits{0.0, 0.0, 0.0, 0.0, 0.0, 0.0};
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    return limits;
                }

                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params(
                    "SELECT for_disabled_persons, general_minimum, COALESCE(age_surcharge_cap, 0.0), COALESCE(age_70_surcharge, 300.0), COALESCE(age_75_surcharge, 456.0), COALESCE(age_80_surcharge, 570.0) FROM subsistence_minimums ORDER BY ABS(year - $1) ASC LIMIT 1",
                    year);
                if (!res.empty())
                {
                    limits.for_disabled_persons = res[0][0].as<double>();
                    limits.general_minimum = res[0][1].as<double>();
                    limits.age_surcharge_cap = res[0][2].as<double>();
                    limits.age_70_surcharge = res[0][3].as<double>();
                    limits.age_75_surcharge = res[0][4].as<double>();
                    limits.age_80_surcharge = res[0][5].as<double>();
                    return limits;
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception getSubsistenceLimits] " << e.what() << std::endl;
            }
            return limits;
        }

        bool CoefficientRepository::upsertSubsistenceLimits(int year, double for_disabled, double general, double age_surcharge_cap, double age_70, double age_75, double age_80)
        {
            mock_limits_[year] = service::SubsistenceLimits{for_disabled, general, age_surcharge_cap, age_70, age_75, age_80};
            if (mock_mode_)
            {
                return true;
            }

            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    return false;
                }

                pqxx::work txn(conn);
                txn.exec_params(
                    "INSERT INTO subsistence_minimums (year, for_disabled_persons, general_minimum, age_surcharge_cap, age_70_surcharge, age_75_surcharge, age_80_surcharge) VALUES ($1, $2, $3, $4, $5, $6, $7) ON CONFLICT (year) DO UPDATE SET for_disabled_persons = EXCLUDED.for_disabled_persons, general_minimum = EXCLUDED.general_minimum, age_surcharge_cap = EXCLUDED.age_surcharge_cap, age_70_surcharge = EXCLUDED.age_70_surcharge, age_75_surcharge = EXCLUDED.age_75_surcharge, age_80_surcharge = EXCLUDED.age_80_surcharge, updated_at = CURRENT_TIMESTAMP",
                    year, for_disabled, general, age_surcharge_cap, age_70, age_75, age_80);
                txn.commit();
                return true;
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception upsertSubsistenceLimits] " << e.what() << std::endl;
            }
            return false;
        }

        std::vector<SubsistenceMinimumRecord> CoefficientRepository::listSubsistenceMinimums() const
        {
            std::vector<SubsistenceMinimumRecord> results;
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    return results;
                }

                pqxx::work txn(conn);
                pqxx::result res = txn.exec(
                    "SELECT id, year, for_disabled_persons, general_minimum, COALESCE(age_surcharge_cap, 0.0), COALESCE(age_70_surcharge, 0.0), COALESCE(age_75_surcharge, 0.0), COALESCE(age_80_surcharge, 0.0) FROM subsistence_minimums ORDER BY year DESC");
                txn.commit();

                for (const auto &row : res)
                {
                    SubsistenceMinimumRecord rec;
                    rec.id = row[0].as<int>();
                    rec.year = row[1].as<int>();
                    rec.for_disabled_persons = row[2].as<double>();
                    rec.general_minimum = row[3].as<double>();
                    rec.age_surcharge_cap = row[4].as<double>();
                    rec.age_70_surcharge = row[5].as<double>();
                    rec.age_75_surcharge = row[6].as<double>();
                    rec.age_80_surcharge = row[7].as<double>();
                    results.push_back(rec);
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception listSubsistenceMinimums] " << e.what() << std::endl;
            }
            return results;
        }

        std::optional<SubsistenceMinimumRecord> CoefficientRepository::updateSubsistenceMinimum(int id, int year, double for_disabled, double general, double age_surcharge_cap, double age_70, double age_75, double age_80)
        {
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                    return std::nullopt;

                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params(
                    "UPDATE subsistence_minimums SET year = $1, for_disabled_persons = $2, general_minimum = $3, age_surcharge_cap = $4, age_70_surcharge = $5, age_75_surcharge = $6, age_80_surcharge = $7, updated_at = CURRENT_TIMESTAMP WHERE id = $8 RETURNING id, year, for_disabled_persons, general_minimum, COALESCE(age_surcharge_cap, 0.0), COALESCE(age_70_surcharge, 0.0), COALESCE(age_75_surcharge, 0.0), COALESCE(age_80_surcharge, 0.0)",
                    year, for_disabled, general, age_surcharge_cap, age_70, age_75, age_80, id);
                txn.commit();

                if (!res.empty())
                {
                    SubsistenceMinimumRecord rec;
                    rec.id = res[0][0].as<int>();
                    rec.year = res[0][1].as<int>();
                    rec.for_disabled_persons = res[0][2].as<double>();
                    rec.general_minimum = res[0][3].as<double>();
                    rec.age_surcharge_cap = res[0][4].as<double>();
                    rec.age_70_surcharge = res[0][5].as<double>();
                    rec.age_75_surcharge = res[0][6].as<double>();
                    rec.age_80_surcharge = res[0][7].as<double>();
                    return rec;
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception updateSubsistenceMinimum] " << e.what() << std::endl;
            }
            return std::nullopt;
        }

        bool CoefficientRepository::deleteSubsistenceMinimum(int id)
        {
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                    return false;

                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params("DELETE FROM subsistence_minimums WHERE id = $1", id);
                txn.commit();
                return res.affected_rows() > 0;
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception deleteSubsistenceMinimum] " << e.what() << std::endl;
            }
            return false;
        }

    }
}
