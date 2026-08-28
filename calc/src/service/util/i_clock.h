#ifndef I_CLOCK_H
#define I_CLOCK_H

#include <ctime>

namespace calc
{
    namespace service
    {
        class IClock
        {
        public:
            virtual ~IClock() = default;
            virtual int currentYear() const = 0;
        };

        class SystemClock : public IClock
        {
        public:
            int currentYear() const override
            {
                std::time_t t = std::time(nullptr);
                std::tm *now = std::localtime(&t);
                return (now != nullptr) ? (now->tm_year + 1900) : 2026;
            }
        };
    }
}

#endif // I_CLOCK_H
