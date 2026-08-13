#include "coefficient_repository.h"
#include "../db/db_config.h"
#include <pqxx/pqxx>
#include <iostream>

namespace calc {
namespace repository {

double CoefficientRepository::getCoefficient(int year, int month) {
    try {
        pqxx::connection conn(db::DbConfig::getConnectionString());
        if (!conn.is_open()) {
            std::cerr << "[Calc DB] Failed to open PostgreSQL connection." << std::endl;
            return 1.0;
        }

        pqxx::work txn(conn);
        pqxx::result res = txn.exec_params(
            "SELECT coefficient FROM pension_coefficients WHERE year = $1 AND month = $2 LIMIT 1",
            year, month
        );
        txn.commit();

        if (!res.empty()) {
            double coef = res[0][0].as<double>();
            std::cout << "[Calc DB] Found coefficient for " << year << "-" << month << ": " << coef << std::endl;
            return coef;
        }
    } catch (const std::exception& e) {
        std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
    }

    return 1.0;
}

std::vector<CoefficientRecord> CoefficientRepository::listAll() {
    std::vector<CoefficientRecord> results;
    try {
        pqxx::connection conn(db::DbConfig::getConnectionString());
        pqxx::work txn(conn);
        pqxx::result res = txn.exec(
            "SELECT id, year, month, coefficient, COALESCE(description, '') FROM pension_coefficients ORDER BY year DESC, month DESC"
        );
        txn.commit();

        for (const auto& row : res) {
            CoefficientRecord rec;
            rec.id = row[0].as<int>();
            rec.year = row[1].as<int>();
            rec.month = row[2].as<int>();
            rec.coefficient = row[3].as<double>();
            rec.description = row[4].as<std::string>();
            results.push_back(rec);
        }
    } catch (const std::exception& e) {
        std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
    }
    return results;
}

std::optional<CoefficientRecord> CoefficientRepository::add(int year, int month, double coefficient, const std::string& description) {
    try {
        pqxx::connection conn(db::DbConfig::getConnectionString());
        pqxx::work txn(conn);
        pqxx::result res = txn.exec_params(
            "INSERT INTO pension_coefficients (year, month, coefficient, description) VALUES ($1, $2, $3, $4) ON CONFLICT (year, month) DO UPDATE SET coefficient = EXCLUDED.coefficient, description = EXCLUDED.description RETURNING id, year, month, coefficient, COALESCE(description, '')",
            year, month, coefficient, description
        );
        txn.commit();

        if (!res.empty()) {
            CoefficientRecord rec;
            rec.id = res[0][0].as<int>();
            rec.year = res[0][1].as<int>();
            rec.month = res[0][2].as<int>();
            rec.coefficient = res[0][3].as<double>();
            rec.description = res[0][4].as<std::string>();
            return rec;
        }
    } catch (const std::exception& e) {
        std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
    }
    return std::nullopt;
}

std::optional<CoefficientRecord> CoefficientRepository::update(int id, int year, int month, double coefficient, const std::string& description) {
    try {
        pqxx::connection conn(db::DbConfig::getConnectionString());
        pqxx::work txn(conn);
        pqxx::result res = txn.exec_params(
            "UPDATE pension_coefficients SET year = $1, month = $2, coefficient = $3, description = $4, updated_at = CURRENT_TIMESTAMP WHERE id = $5 RETURNING id, year, month, coefficient, COALESCE(description, '')",
            year, month, coefficient, description, id
        );
        txn.commit();

        if (!res.empty()) {
            CoefficientRecord rec;
            rec.id = res[0][0].as<int>();
            rec.year = res[0][1].as<int>();
            rec.month = res[0][2].as<int>();
            rec.coefficient = res[0][3].as<double>();
            rec.description = res[0][4].as<std::string>();
            return rec;
        }
    } catch (const std::exception& e) {
        std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
    }
    return std::nullopt;
}

bool CoefficientRepository::remove(int id) {
    try {
        pqxx::connection conn(db::DbConfig::getConnectionString());
        pqxx::work txn(conn);
        pqxx::result res = txn.exec_params("DELETE FROM pension_coefficients WHERE id = $1", id);
        txn.commit();
        return res.affected_rows() > 0;
    } catch (const std::exception& e) {
        std::cerr << "[Calc DB Exception] " << e.what() << std::endl;
    }
    return false;
}

}
}
