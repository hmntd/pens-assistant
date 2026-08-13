#ifndef DB_CONFIG_H
#define DB_CONFIG_H

#include <string>

namespace calc {
namespace db {

class DbConfig {
public:
    static std::string getConnectionString();
};

}
}

#endif
