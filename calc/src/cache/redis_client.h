#ifndef REDIS_CLIENT_H
#define REDIS_CLIENT_H

#include <string>
#include <optional>
#include <vector>

namespace calc
{
    namespace cache
    {

        class RedisClient
        {
        public:
            static std::string getHost();
            static int getPort();
            static bool isEnabled();

            static std::optional<std::string> get(const std::string &key);
            static bool set(const std::string &key, const std::string &value, int ttl_seconds = 3600);
            static bool del(const std::string &key);
            static bool delMany(const std::vector<std::string> &keys);

        private:
            static std::optional<std::string> executeCommand(const std::vector<std::string> &args);
        };

    }
}

#endif

