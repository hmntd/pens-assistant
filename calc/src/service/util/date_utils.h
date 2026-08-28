#ifndef DATE_UTILS_H
#define DATE_UTILS_H

#include <string>

namespace calc
{
    namespace service
    {
        namespace util
        {
            struct YearMonthResult
            {
                bool valid{false};
                int year{0};
                int month{0};
            };

            YearMonthResult parseYearMonth(const std::string &dateStr);

            int ageInYears(const std::string &dateOfBirth, const std::string &retirementDate);
        }
    }
}

#endif // DATE_UTILS_H
