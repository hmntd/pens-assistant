#include "money_format.h"
#include <sstream>
#include <iomanip>

namespace calc
{
    namespace service
    {
        namespace util
        {
            std::string formatUah(double amount)
            {
                std::ostringstream ss;
                ss << std::fixed << std::setprecision(2) << amount;
                return ss.str();
            }

            std::string formatCoefficient(double coeff)
            {
                std::ostringstream ss;
                ss << std::fixed << std::setprecision(4) << coeff;
                return ss.str();
            }
        }
    }
}
