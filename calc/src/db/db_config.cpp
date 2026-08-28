#include "db_config.h"
#include <cstdlib>

namespace calc
{
    namespace db
    {

        std::string DbConfig::getConnectionString()
        {
            const char *host = std::getenv("DB_HOST") ? std::getenv("DB_HOST") : "calc_db";
            const char *port = std::getenv("DB_PORT") ? std::getenv("DB_PORT") : "5432";
            const char *dbname = std::getenv("DB_NAME") ? std::getenv("DB_NAME") : "calc_db";
            const char *user = std::getenv("DB_USER") ? std::getenv("DB_USER") : "postgres";
            const char *password = std::getenv("DB_PASSWORD") ? std::getenv("DB_PASSWORD") : "password";

            return "host=" + std::string(host) +
                   " port=" + std::string(port) +
                   " dbname=" + std::string(dbname) +
                   " user=" + std::string(user) +
                   " password=" + std::string(password);
        }

    }
}
