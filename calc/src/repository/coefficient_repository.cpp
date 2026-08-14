#include "coefficient_repository.h"
#include "../db/db_config.h"
#include <pqxx/pqxx>
#include <iostream>

namespace calc
{
    namespace repository
    {

        double CoefficientRepository::getCoefficient(int year, int month)
        {
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
                if (!res.empty())
                {
                    return res[0][0].as<double>();
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception getAverageSalary] " << e.what() << std::endl;
            }
            return 0.0;
        }

        bool CoefficientRepository::upsertAverageSalary(int year, int month, double amount)
        {
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
            service::SubsistenceLimits limits{0.0, 0.0};
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    return limits;
                }

                pqxx::work txn(conn);
                // Order by absolute difference to select current year or nearest year
                pqxx::result res = txn.exec_params(
                    "SELECT for_disabled_persons, general_minimum FROM subsistence_minimums ORDER BY ABS(year - $1) ASC LIMIT 1",
                    year);
                if (!res.empty())
                {
                    limits.for_disabled_persons = res[0][0].as<double>();
                    limits.general_minimum = res[0][1].as<double>();
                    return limits;
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception getSubsistenceLimits] " << e.what() << std::endl;
            }
            return limits;
        }

        bool CoefficientRepository::upsertSubsistenceLimits(int year, double for_disabled, double general)
        {
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                {
                    return false;
                }

                pqxx::work txn(conn);
                txn.exec_params(
                    "INSERT INTO subsistence_minimums (year, for_disabled_persons, general_minimum) VALUES ($1, $2, $3) ON CONFLICT (year) DO UPDATE SET for_disabled_persons = EXCLUDED.for_disabled_persons, general_minimum = EXCLUDED.general_minimum, updated_at = CURRENT_TIMESTAMP",
                    year, for_disabled, general);
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
                    "SELECT id, year, for_disabled_persons, general_minimum FROM subsistence_minimums ORDER BY year DESC");
                txn.commit();

                for (const auto &row : res)
                {
                    SubsistenceMinimumRecord rec;
                    rec.id = row[0].as<int>();
                    rec.year = row[1].as<int>();
                    rec.for_disabled_persons = row[2].as<double>();
                    rec.general_minimum = row[3].as<double>();
                    results.push_back(rec);
                }
            }
            catch (const std::exception &e)
            {
                std::cerr << "[Calc DB Exception listSubsistenceMinimums] " << e.what() << std::endl;
            }
            return results;
        }

        std::optional<SubsistenceMinimumRecord> CoefficientRepository::updateSubsistenceMinimum(int id, int year, double for_disabled, double general)
        {
            try
            {
                pqxx::connection conn(db::DbConfig::getConnectionString());
                if (!conn.is_open())
                    return std::nullopt;

                pqxx::work txn(conn);
                pqxx::result res = txn.exec_params(
                    "UPDATE subsistence_minimums SET year = $1, for_disabled_persons = $2, general_minimum = $3, updated_at = CURRENT_TIMESTAMP WHERE id = $4 RETURNING id, year, for_disabled_persons, general_minimum",
                    year, for_disabled, general, id);
                txn.commit();

                if (!res.empty())
                {
                    SubsistenceMinimumRecord rec;
                    rec.id = res[0][0].as<int>();
                    rec.year = res[0][1].as<int>();
                    rec.for_disabled_persons = res[0][2].as<double>();
                    rec.general_minimum = res[0][3].as<double>();
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
