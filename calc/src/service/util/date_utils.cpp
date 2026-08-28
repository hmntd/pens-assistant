#include "date_utils.h"
#include <exception>

namespace calc
{
    namespace service
    {
        namespace util
        {
            YearMonthResult parseYearMonth(const std::string &dateStr)
            {
                YearMonthResult res;
                if (dateStr.length() < 7)
                {
                    return res;
                }

                try
                {
                    res.year = std::stoi(dateStr.substr(0, 4));
                    res.month = std::stoi(dateStr.substr(5, 2));
                    if (res.month >= 1 && res.month <= 12 && res.year > 1900)
                    {
                        res.valid = true;
                    }
                }
                catch (const std::exception &)
                {
                    res.valid = false;
                }

                return res;
            }

            int ageInYears(const std::string &dateOfBirth, const std::string &retirementDate)
            {
                if (dateOfBirth.length() < 4 || retirementDate.length() < 4)
                {
                    return 0;
                }

                try
                {
                    int birth_year = std::stoi(dateOfBirth.substr(0, 4));
                    int ret_year = std::stoi(retirementDate.substr(0, 4));
                    int age = ret_year - birth_year;

                    if (dateOfBirth.length() >= 7 && retirementDate.length() >= 7)
                    {
                        int birth_month = std::stoi(dateOfBirth.substr(5, 2));
                        int ret_month = std::stoi(retirementDate.substr(5, 2));

                        if (ret_month < birth_month)
                        {
                            age--;
                        }
                        else if (
                            ret_month == birth_month &&
                            dateOfBirth.length() >= 10 &&
                            retirementDate.length() >= 10
                        )
                        {
                            int birth_day = std::stoi(dateOfBirth.substr(8, 2));
                            int ret_day = std::stoi(retirementDate.substr(8, 2));
                            if (ret_day < birth_day)
                            {
                                age--;
                            }
                        }
                    }

                    return age > 0 ? age : 0;
                }
                catch (const std::exception &)
                {
                    return 0;
                }
            }
        }
    }
}
