#include "service_coefficient_stage.h"
#include "../util/date_utils.h"
#include "../util/money_format.h"
#include <sstream>
#include <exception>

namespace calc
{
    namespace service
    {
        bool ServiceCoefficientStage::execute(PensionCalculationContext &ctx, std::string &error) const
        {
            const auto *request = ctx.request;
            int total_months = 0;

            if (request->employment_history_size() > 0)
            {
                for (const auto &period : request->employment_history())
                {
                    auto start_ym = util::parseYearMonth(period.start_date());
                    auto end_ym = util::parseYearMonth(period.end_date());

                    if (!start_ym.valid || !end_ym.valid)
                    {
                        error = "Invalid employment date format for period: " + period.start_date() + " to " + period.end_date();
                        return false;
                    }

                    int months = (end_ym.year - start_ym.year) * 12 + (end_ym.month - start_ym.month) + 1;
                    if (months <= 0)
                    {
                        error = "Employment start date is after end date: " + period.start_date() + " to " + period.end_date();
                        return false;
                    }

                    double mult = period.multiplier() > 0.0 ? period.multiplier() : 1.0;
                    total_months += static_cast<int>(months * mult);
                }
            }
            else if (request->history_size() > 0)
            {
                for (const auto &rec : request->history())
                {
                    if (rec.months_worked() > 0)
                    {
                        total_months += rec.months_worked();
                    }
                }
            }

            if (total_months <= 0)
            {
                error = "Employment history is empty or zero service months recorded";
                return false;
            }

            if (ctx.is_hypothetical_mode && ctx.retirement_year > ctx.current_year)
            {
                int proj_years = ctx.retirement_year - ctx.current_year;
                int proj_months = proj_years * 12;
                total_months += proj_months;

                std::ostringstream ss;
                ss << "Hypothetical Projection: Projected additional " << proj_months << " service months ("
                   << proj_years << " years) up to target retirement year " << ctx.retirement_year << ".";
                ctx.logs.push_back(ss.str());
            }

            double ks = static_cast<double>(total_months) / 1200.0;

            bool is_female = request->gender() == calc::Gender::FEMALE;
            int required_months = is_female ? 360 : 420; // 30 years for women, 35 years for men
            int overtime_months = total_months > required_months ? (total_months - required_months) : 0;

            ctx.total_service_months = total_months;
            ctx.overtime_service_months = overtime_months;
            ctx.ks_service_coefficient = ks;

            std::ostringstream ss;
            ss << "Stage 1 [Ks Service Coefficient Art. 24 Law 1058-IV]: Total Insurance Service = "
               << total_months << " months (" << (total_months / 12) << " years, " << (total_months % 12)
               << " months). Ks = " << util::formatCoefficient(ks) << ".";
            ctx.logs.push_back(ss.str());

            return true;
        }
    }
}
