#ifndef LEGACY_REQUEST_ADAPTER_H
#define LEGACY_REQUEST_ADAPTER_H

#include "calc.pb.h"

namespace calc
{
    namespace service
    {
        class LegacyRequestAdapter
        {
        public:
            calc::CalculatePensionRequest adapt(const calc::PensionRequest &legacyRequest) const;
        };
    }
}

#endif // LEGACY_REQUEST_ADAPTER_H
