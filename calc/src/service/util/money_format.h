#ifndef MONEY_FORMAT_H
#define MONEY_FORMAT_H

#include <string>

namespace calc
{
    namespace service
    {
        namespace util
        {
            std::string formatUah(double amount);
            std::string formatCoefficient(double coeff);
        }
    }
}

#endif // MONEY_FORMAT_H
