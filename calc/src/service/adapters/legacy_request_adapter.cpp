#include "legacy_request_adapter.h"

namespace calc
{
    namespace service
    {
        calc::CalculatePensionRequest LegacyRequestAdapter::adapt(const calc::PensionRequest &legacyRequest) const
        {
            calc::CalculatePensionRequest new_req;
            new_req.set_customer_id(legacyRequest.customer_id());
            new_req.set_birth_year(legacyRequest.birth_year());
            new_req.set_target_retirement_year(legacyRequest.target_retirement_year());

            for (int i = 0; i < legacyRequest.history_size(); ++i)
            {
                *new_req.add_history() = legacyRequest.history(i);
            }

            return new_req;
        }
    }
}
