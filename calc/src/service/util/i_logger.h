#ifndef I_LOGGER_H
#define I_LOGGER_H

#include <string>
#include <iostream>

namespace calc
{
    namespace service
    {
        class ILogger
        {
        public:
            virtual ~ILogger() = default;
            virtual void info(const std::string &msg) const = 0;
            virtual void error(const std::string &msg) const = 0;
        };

        class ConsoleLogger : public ILogger
        {
        public:
            void info(const std::string &msg) const override
            {
                std::cout << "[Calc Engine] " << msg << std::endl;
            }

            void error(const std::string &msg) const override
            {
                std::cerr << "[Calc Engine Error] " << msg << std::endl;
            }
        };
    }
}

#endif // I_LOGGER_H
