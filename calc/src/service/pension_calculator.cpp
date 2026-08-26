#include "pension_calculator.h"
#include <iostream>
#include <cmath>
#include <algorithm>
#include <numeric>
#include <sstream>
#include <iomanip>
#include <stdexcept>
#include <chrono>
#include <ctime>

namespace calc
{
    namespace service
    {

        PensionCalculator::PensionCalculator(repository::CoefficientRepository repo)
            : repo_(std::move(repo)), benefit_engine_() {}

        int PensionCalculator::calculateTotalServiceMonths(
            const calc::CalculatePensionRequest *request,
            int effective_retirement_year,
            bool is_hypothetical_mode,
            std::vector<std::string> &logs,
            std::string &error_message) const
        {
            int total_months = 0;
            int last_year = 0;
            int last_month = 0;

            if (request->employment_history_size() > 0)
            {
                for (const auto &period : request->employment_history())
                {
                    if (period.start_date().length() < 7 || period.end_date().length() < 7)
                    {
                        error_message = "Employment period has an invalid or missing date (expected YYYY-MM): start='" +
                                        period.start_date() + "', end='" + period.end_date() + "'";
                        return -1;
                    }

                    int start_year, start_month, end_year, end_month;
                    try
                    {
                        start_year = std::stoi(period.start_date().substr(0, 4));
                        start_month = std::stoi(period.start_date().substr(5, 2));
                        end_year = std::stoi(period.end_date().substr(0, 4));
                        end_month = std::stoi(period.end_date().substr(5, 2));
                    }
                    catch (const std::exception &)
                    {
                        error_message = "Employment period contains a non-numeric date: start='" +
                                        period.start_date() + "', end='" + period.end_date() + "'";
                        return -1;
                    }

                    if (start_month < 1 || start_month > 12 || end_month < 1 || end_month > 12)
                    {
                        error_message = "Employment period has an out-of-range month: start='" +
                                        period.start_date() + "', end='" + period.end_date() + "'";
                        return -1;
                    }

                    int months = (end_year - start_year) * 12 + (end_month - start_month + 1);
                    if (months < 1)
                    {
                        error_message = "Employment period end date precedes start date: start='" +
                                        period.start_date() + "', end='" + period.end_date() + "'";
                        return -1;
                    }

                    double mult = period.multiplier() > 0.0 ? period.multiplier() : 1.0;
                    total_months += static_cast<int>(std::round(months * mult));

                    if (end_year > last_year || (end_year == last_year && end_month > last_month))
                    {
                        last_year = end_year;
                        last_month = end_month;
                    }
                }
            }
            else if (request->history_size() > 0)
            {
                for (const auto &rec : request->history())
                {
                    if (rec.months_worked() <= 0)
                    {
                        error_message = "History record for year " + std::to_string(rec.year()) +
                                        " is missing months_worked (must be > 0)";
                        return -1;
                    }
                    total_months += rec.months_worked();

                    if (rec.year() > last_year)
                    {
                        last_year = rec.year();
                        last_month = std::min(12, rec.months_worked());
                    }
                }
            }
            else
            {
                error_message = "No employment_history or legacy history provided; cannot determine insurance experience";
                return -1;
            }

            if (is_hypothetical_mode && effective_retirement_year > getCurrentYear())
            {
                if (last_year == 0)
                {
                    last_year = getCurrentYear();
                    last_month = 1;
                }

                int target_end_year = effective_retirement_year;
                int target_end_month = 12;
                if (request->retirement_date().length() >= 7)
                {
                    try
                    {
                        target_end_month = std::stoi(request->retirement_date().substr(5, 2));
                    }
                    catch (const std::exception &)
                    {
                    }
                }

                int future_proj_months = (target_end_year - last_year) * 12 + (target_end_month - last_month);
                if (future_proj_months > 0)
                {
                    total_months += future_proj_months;
                    logs.push_back("Hypothetical Projection: Added " + std::to_string(future_proj_months) +
                                   " projected working months up to target retirement year " + std::to_string(target_end_year));
                }
            }

            return total_months;
        }

        struct MonthRatioItem
        {
            int year;
            int month;
            double ratio;
            bool is_special_period;
        };

        double PensionCalculator::calculateWageCoefficientKz(
            const calc::CalculatePensionRequest *request,
            int effective_retirement_year,
            bool is_hypothetical_mode,
            std::vector<std::string> &logs,
            std::string &error_message) const
        {
            std::vector<MonthRatioItem> raw_pre_2000_items;
            std::vector<MonthRatioItem> post_2000_items;
            int last_year = 0;
            int last_month = 0;
            double last_monthly_income = 0.0;

            if (request->salary_history_size() > 0)
            {
                for (const auto &rec : request->salary_history())
                {
                    double avg_national = repo_.getAverageSalary(rec.year(), rec.month());
                    if (avg_national <= 0.0)
                    {
                        error_message = "Missing national average salary data in DB for year " +
                                        std::to_string(rec.year()) + ", month " + std::to_string(rec.month());
                        return -1.0;
                    }

                    if (rec.amount() <= 0.0)
                    {
                        // Skip zero-amount or service-only salary entries from Kz ratio calculation
                        continue;
                    }

                    double ratio = rec.amount() / avg_national;

                    bool is_special = rec.is_special_period();
                    if (!is_special)
                    {
                        int yr = rec.year();
                        int mo = rec.month();
                        if ((yr == 2020 && mo >= 3) || yr == 2021 || yr == 2022 || (yr == 2023 && mo <= 6))
                        {
                            is_special = true;
                        }
                        if ((yr == 2022 && mo >= 2) || yr >= 2023)
                        {
                            is_special = true;
                        }
                    }

                    MonthRatioItem item{rec.year(), rec.month(), ratio, is_special};

                    if (rec.year() < 2000 || (rec.year() == 2000 && rec.month() <= 6))
                    {
                        raw_pre_2000_items.push_back(item);
                    }
                    else
                    {
                        post_2000_items.push_back(item);
                    }

                    if (rec.year() > last_year || (rec.year() == last_year && rec.month() > last_month))
                    {
                        last_year = rec.year();
                        last_month = rec.month();
                        last_monthly_income = rec.amount();
                    }
                }
            }
            else if (request->history_size() > 0)
            {
                for (const auto &rec : request->history())
                {
                    double avg_national = repo_.getAverageSalary(rec.year(), 1);
                    if (avg_national <= 0.0)
                    {
                        error_message = "Missing national average salary data in DB for year " + std::to_string(rec.year());
                        return -1.0;
                    }

                    if (rec.months_worked() <= 0)
                    {
                        error_message = "History record for year " + std::to_string(rec.year()) +
                                        " is missing months_worked (must be > 0); cannot derive monthly income";
                        return -1.0;
                    }

                    if (rec.annual_income() <= 0.0)
                    {
                        // Skip zero-income or service-only records from Kz ratio calculation
                        continue;
                    }

                    double monthly_income = rec.annual_income() / rec.months_worked();
                    double ratio = monthly_income / avg_national;

                    int yr = rec.year();
                    bool is_special = (yr >= 2020);

                    for (int m = 1; m <= rec.months_worked(); ++m)
                    {
                        MonthRatioItem item{yr, m, ratio, is_special};
                        if (yr < 2000 || (yr == 2000 && m <= 6))
                        {
                            raw_pre_2000_items.push_back(item);
                        }
                        else
                        {
                            post_2000_items.push_back(item);
                        }
                    }

                    if (rec.year() > last_year)
                    {
                        last_year = rec.year();
                        last_month = std::min(12, rec.months_worked());
                        last_monthly_income = monthly_income;
                    }
                }
            }
            else
            {
                error_message = "No salary_history or legacy history provided; cannot calculate wage coefficient Kz";
                return -1.0;
            }

            if (is_hypothetical_mode && effective_retirement_year > getCurrentYear() && last_monthly_income > 0.0)
            {
                int current_yr = getCurrentYear();
                double latest_national_avg = repo_.getAverageSalary(current_yr, 1);
                if (latest_national_avg <= 0.0)
                {
                    latest_national_avg = 20000.0;
                }

                double proj_ratio = last_monthly_income / latest_national_avg;

                int target_end_year = effective_retirement_year;
                int target_end_month = 12;
                if (request->retirement_date().length() >= 7)
                {
                    try
                    {
                        target_end_month = std::stoi(request->retirement_date().substr(5, 2));
                    }
                    catch (const std::exception &)
                    {
                    }
                }

                int curr_y = last_year;
                int curr_m = last_month + 1;
                if (curr_m > 12)
                {
                    curr_m = 1;
                    curr_y++;
                }

                int proj_count = 0;
                while (curr_y < target_end_year || (curr_y == target_end_year && curr_m <= target_end_month))
                {
                    post_2000_items.push_back({curr_y, curr_m, proj_ratio, true});
                    proj_count++;

                    curr_m++;
                    if (curr_m > 12)
                    {
                        curr_m = 1;
                        curr_y++;
                    }
                }

                if (proj_count > 0)
                {
                    logs.push_back("Hypothetical Projection: Projected " + std::to_string(proj_count) +
                                   " future monthly salary records at last known income (" +
                                   std::to_string(static_cast<int>(last_monthly_income)) + " UAH/mo)");
                }
            }

            std::vector<MonthRatioItem> items;
            std::vector<MonthRatioItem> selected_pre_2000_items;

            if (!raw_pre_2000_items.empty())
            {
                std::sort(raw_pre_2000_items.begin(), raw_pre_2000_items.end(),
                          [](const MonthRatioItem &a, const MonthRatioItem &b)
                          {
                              if (a.year != b.year)
                                  return a.year < b.year;
                              return a.month < b.month;
                          });

                bool include_pre_2000 = (post_2000_items.size() < 60) || (raw_pre_2000_items.size() >= 60);

                if (include_pre_2000)
                {
                    if (raw_pre_2000_items.size() <= 60)
                    {
                        selected_pre_2000_items = raw_pre_2000_items;
                        std::ostringstream ss;
                        ss << "Stage 2 [Kz Art. 40 Law 1058-IV]: Included all " << selected_pre_2000_items.size()
                           << " pre-July 1, 2000 salary months.";
                        logs.push_back(ss.str());
                    }
                    else
                    {
                        size_t best_start = 0;
                        double max_window_sum = -1.0;
                        for (size_t i = 0; i <= raw_pre_2000_items.size() - 60; ++i)
                        {
                            double w_sum = 0.0;
                            for (size_t j = i; j < i + 60; ++j)
                            {
                                w_sum += raw_pre_2000_items[j].ratio;
                            }
                            if (w_sum > max_window_sum)
                            {
                                max_window_sum = w_sum;
                                best_start = i;
                            }
                        }

                        selected_pre_2000_items.assign(
                            raw_pre_2000_items.begin() + best_start,
                            raw_pre_2000_items.begin() + best_start + 60);

                        std::ostringstream ss;
                        ss << "Stage 2 [Kz Art. 40 Law 1058-IV]: Selected optimal 60 consecutive pre-July 1, 2000 salary months (from "
                           << raw_pre_2000_items[best_start].year << "-"
                           << (raw_pre_2000_items[best_start].month < 10 ? "0" : "") << raw_pre_2000_items[best_start].month
                           << " to " << raw_pre_2000_items[best_start + 59].year << "-"
                           << (raw_pre_2000_items[best_start + 59].month < 10 ? "0" : "") << raw_pre_2000_items[best_start + 59].month
                           << ") out of " << raw_pre_2000_items.size() << " available pre-2000 months.";
                        logs.push_back(ss.str());
                    }
                }
                else
                {
                    std::ostringstream ss;
                    ss << "Stage 2 [Kz Art. 40 Law 1058-IV]: Pre-July 1, 2000 salary history omitted from Kz calculation "
                       << "(has " << raw_pre_2000_items.size() << " pre-2000 salary months; Art. 40 requires 60 consecutive months of salary history when post-2000 experience is >= 60 months). "
                       << "Pre-2000 period counted towards insurance service Ks.";
                    logs.push_back(ss.str());
                }
            }

            items.insert(items.end(), selected_pre_2000_items.begin(), selected_pre_2000_items.end());
            items.insert(items.end(), post_2000_items.begin(), post_2000_items.end());

            if (items.empty())
            {
                logs.push_back("Stage 2 [Kz Wage Coefficient]: Service-only history provided without salary entries. Defaulting Kz = 1.0000.");
                return 1.0;
            }

            double initial_sum = 0.0;
            for (const auto &it : items)
            {
                initial_sum += it.ratio;
            }
            double initial_kz = initial_sum / items.size();
            size_t total_months = items.size();

            if (request->enable_optimization_rule())
            {
                size_t max_total_droppable = (total_months > 60) ? (total_months - 60) : 0;
                size_t max_10pct_droppable = std::min(static_cast<size_t>(std::floor(total_months * 0.10)), max_total_droppable);

                if (max_total_droppable > 0)
                {
                    std::vector<MonthRatioItem> sorted_items = items;
                    std::sort(sorted_items.begin(), sorted_items.end(), [](const MonthRatioItem &a, const MonthRatioItem &b)
                              { return a.ratio < b.ratio; });

                    std::vector<MonthRatioItem> remaining_items;
                    size_t total_dropped = 0;
                    size_t pct10_dropped = 0;
                    size_t special_dropped = 0;
                    double current_sum = initial_sum;
                    size_t current_count = total_months;

                    for (const auto &it : sorted_items)
                    {
                        double current_kz = current_sum / current_count;

                        if (it.ratio < current_kz && total_dropped < max_total_droppable)
                        {
                            if (it.is_special_period)
                            {
                                current_sum -= it.ratio;
                                current_count--;
                                total_dropped++;
                                special_dropped++;
                                continue;
                            }
                            else if (pct10_dropped < max_10pct_droppable)
                            {
                                current_sum -= it.ratio;
                                current_count--;
                                total_dropped++;
                                pct10_dropped++;
                                continue;
                            }
                        }

                        remaining_items.push_back(it);
                    }

                    if (total_dropped > 0 && current_count >= 60)
                    {
                        double opt_kz = current_sum / current_count;
                        if (opt_kz > initial_kz)
                        {
                            std::ostringstream ss;
                            ss << "Optimization Rule Applied (Law 1058-IV Art. 40): Dropped " << total_dropped
                               << " worst salary months (" << special_dropped << " special statutory period months, "
                               << pct10_dropped << " under 10% rule). Remaining evaluated period: " << current_count
                               << " months (min 60 months floor maintained). Kz improved from "
                               << std::fixed << std::setprecision(4) << initial_kz << " to " << opt_kz;
                            logs.push_back(ss.str());
                            return opt_kz;
                        }
                    }
                }
            }

            std::ostringstream ss;
            ss << "Wage coefficient Kz calculated over " << items.size() << " months: "
               << std::fixed << std::setprecision(4) << initial_kz;
            logs.push_back(ss.str());

            return initial_kz;
        }

        double PensionCalculator::calculatePensionTypeModifier(
            const calc::CalculatePensionRequest *request,
            std::vector<std::string> &logs,
            std::string &error_message) const
        {
            double modifier = 0.0;
            std::string type_str;

            switch (request->pension_type())
            {
            case calc::PensionType::OLD_AGE:
                modifier = 1.0;
                type_str = "OLD_AGE (100%)";
                break;

            case calc::PensionType::DISABILITY:
                switch (request->disability_group())
                {
                case calc::DisabilityGroup::GROUP_1:
                    modifier = 1.0;
                    type_str = "DISABILITY Group I (100%)";
                    break;
                case calc::DisabilityGroup::GROUP_2:
                    modifier = 0.90;
                    type_str = "DISABILITY Group II (90%)";
                    break;
                case calc::DisabilityGroup::GROUP_3:
                    modifier = 0.50;
                    type_str = "DISABILITY Group III (50%)";
                    break;
                default:
                    error_message = "Pension type is DISABILITY but disability_group is missing or invalid; "
                                    "a valid group (I, II, or III) is required";
                    return -1.0;
                }
                break;

            case calc::PensionType::LOSS_OF_BREADWINNER:
                if (request->dependents_count() <= 0)
                {
                    error_message = "Pension type is LOSS_OF_BREADWINNER but dependents_count is missing or zero; "
                                    "at least 1 dependent is required";
                    return -1.0;
                }
                else if (request->dependents_count() == 1)
                {
                    modifier = 0.50;
                    type_str = "LOSS_OF_BREADWINNER 1 Dependent (50%)";
                }
                else
                {
                    modifier = 1.0;
                    type_str = "LOSS_OF_BREADWINNER 2+ Dependents (100%)";
                }
                break;

            default:
                error_message = "Pension type is missing or unrecognized; a valid pension_type "
                                "(OLD_AGE, DISABILITY, or LOSS_OF_BREADWINNER) is required";
                return -1.0;
            }

            logs.push_back("Pension Type Modifier applied: " + type_str + " = " + std::to_string(modifier));
            return modifier;
        }

        double PensionCalculator::calculateExtraServiceAllowance(
            const calc::CalculatePensionRequest *request,
            int total_months,
            double base_pension,
            const SubsistenceLimits &limits,
            std::vector<std::string> &logs) const
        {
            // Check if pension was assigned before October 1, 2011 (Law 1058-IV Art. 28)
            bool is_legacy_pre_2011 = false;
            if (request->retirement_date().length() >= 10)
            {
                if (request->retirement_date() < "2011-10-01")
                {
                    is_legacy_pre_2011 = true;
                }
            }

            int required_years = 35;
            if (request->gender() == calc::Gender::FEMALE)
            {
                required_years = is_legacy_pre_2011 ? 20 : 30;
            }
            else
            {
                required_years = is_legacy_pre_2011 ? 25 : 35;
            }

            int required_months = required_years * 12;
            int extra_months = total_months - required_months;

            if (extra_months <= 0)
            {
                std::ostringstream ss;
                ss << "Stage 3 [Overtime Allowance Art. 28]: 0.00 UAH (service experience "
                   << (total_months / 12) << " yrs (" << total_months << " mos) does not exceed statutory norm of "
                   << required_years << " yrs (" << required_months << " mos) for "
                   << (request->gender() == calc::Gender::FEMALE ? "Women" : "Men") << ")";
                logs.push_back(ss.str());
                return 0.0;
            }

            int extra_full_years = extra_months / 12;
            if (extra_full_years <= 0)
            {
                std::ostringstream ss;
                ss << "Stage 3 [Overtime Allowance Art. 28]: 0.00 UAH (less than 1 full extra year over statutory norm of "
                   << required_years << " yrs)";
                logs.push_back(ss.str());
                return 0.0;
            }

            double min_limit = limits.for_disabled_persons;
            double base_for_allowance = std::min(base_pension, min_limit);
            double allowance_per_year = 0.01 * base_for_allowance;
            double total_allowance = extra_full_years * allowance_per_year;

            std::ostringstream ss;
            ss << "Stage 3 [Overtime Allowance Art. 28]: " << extra_full_years << " full extra year(s) over statutory norm of "
               << required_years << " yrs (" << required_months << " mos) for "
               << (request->gender() == calc::Gender::FEMALE ? "Women" : "Men")
               << ". 1% per year capped at 1% of subsistence min (" << std::fixed << std::setprecision(2) << min_limit
               << " UAH). Total Overtime Allowance = +" << total_allowance << " UAH";
            logs.push_back(ss.str());

            return total_allowance;
        }

        int PensionCalculator::calculateAgeInYears(const std::string &date_of_birth, const std::string &retirement_date) const
        {
            if (date_of_birth.length() < 10 || retirement_date.length() < 10)
            {
                return -1;
            }

            try
            {
                int dob_y = std::stoi(date_of_birth.substr(0, 4));
                int dob_m = std::stoi(date_of_birth.substr(5, 2));
                int dob_d = std::stoi(date_of_birth.substr(8, 2));

                int ret_y = std::stoi(retirement_date.substr(0, 4));
                int ret_m = std::stoi(retirement_date.substr(5, 2));
                int ret_d = std::stoi(retirement_date.substr(8, 2));

                int age = ret_y - dob_y;
                if (ret_m < dob_m || (ret_m == dob_m && ret_d < dob_d))
                {
                    age--;
                }
                return age;
            }
            catch (const std::exception &)
            {
                return -1;
            }
        }

        double PensionCalculator::calculateAgeSurcharge(
            const calc::CalculatePensionRequest *request,
            double pre_age_pension,
            const SubsistenceLimits &limits,
            std::vector<std::string> &logs,
            SurchargeResult &out_surcharge) const
        {
            int age = calculateAgeInYears(request->date_of_birth(), request->retirement_date());
            if (age < 70)
            {
                return 0.0;
            }

            double age_surcharge_cap = limits.age_surcharge_cap > 0.0 ? limits.age_surcharge_cap : 10340.35;
            if (pre_age_pension >= age_surcharge_cap)
            {
                std::ostringstream ss;
                ss << "Stage 4 [Age Surcharge Exceeded Cap]: Citizen age is " << age
                   << " yrs (eligible for age supplement), but pension payout ("
                   << std::fixed << std::setprecision(2) << pre_age_pension
                   << " UAH) reaches or exceeds legal cap (" << age_surcharge_cap << " UAH). Supplement = 0.00 UAH";
                logs.push_back(ss.str());
                return 0.0;
            }

            double amount = 0.0;
            std::string bracket_name;

            if (age >= 80)
            {
                amount = 570.0;
                bracket_name = "Вікова надбавка до пенсії (80+ років) [+570.00 грн]";
            }
            else if (age >= 75)
            {
                amount = 456.0;
                bracket_name = "Вікова надбавка до пенсії (75-79 років) [+456.00 грн]";
            }
            else if (age >= 70)
            {
                amount = 300.0;
                bracket_name = "Вікова надбавка до пенсії (70-74 років) [+300.00 грн]";
            }

            double final_amount = amount;
            if (pre_age_pension + amount > age_surcharge_cap)
            {
                final_amount = age_surcharge_cap - pre_age_pension;
            }

            out_surcharge.type = calc::BenefitType::AGE_SUPPLEMENT;
            out_surcharge.name = bracket_name;
            out_surcharge.amount = final_amount;

            std::ostringstream ss;
            ss << "Stage 4 [Age Surcharge Applied]: Citizen age " << age << " yrs. Supplement = +"
               << std::fixed << std::setprecision(2) << final_amount << " UAH";
            logs.push_back(ss.str());

            return final_amount;
        }

        int PensionCalculator::getCurrentYear() const
        {
            time_t t = time(nullptr);
            struct tm tm_now;
#ifdef _WIN32
            localtime_s(&tm_now, &t);
#else
            localtime_r(&t, &tm_now);
#endif

            return tm_now.tm_year + 1900;
        }

        CalculationResult PensionCalculator::calculate(const calc::CalculatePensionRequest *request)
        {
            CalculationResult res;
            std::vector<std::string> logs;

            std::cout << "[Calc Engine] Executing 5-Stage Pension Calculation for Customer: " << request->customer_id() << std::endl;
            logs.push_back("Starting Pension Calculation Pipeline for Customer: " + request->customer_id());

            int current_year = getCurrentYear();
            int requested_retirement_year;

            if (request->retirement_date().length() >= 4)
            {
                try
                {
                    requested_retirement_year = std::stoi(request->retirement_date().substr(0, 4));
                }
                catch (const std::exception &)
                {
                    res.success = false;
                    res.error_message = "retirement_date is not a valid date: '" + request->retirement_date() + "'";
                    return res;
                }
            }
            else if (request->target_retirement_year() > 0)
            {
                requested_retirement_year = request->target_retirement_year();
            }
            else
            {
                res.success = false;
                res.error_message = "Either retirement_date or target_retirement_year is required to determine "
                                    "the applicable subsistence minimums";
                return res;
            }

            bool is_future_target = (requested_retirement_year > current_year);
            bool enable_hypo_flag = request->enable_hypothetical_projection();

            int retirement_year;
            bool is_hypothetical_mode;

            if (is_future_target)
            {
                if (enable_hypo_flag)
                {
                    retirement_year = requested_retirement_year;
                    is_hypothetical_mode = true;
                    logs.push_back("Target retirement year " + std::to_string(requested_retirement_year) +
                                   " is in the future and hypothetical projection is ENABLED.");
                }
                else
                {
                    retirement_year = current_year;
                    is_hypothetical_mode = false;
                    logs.push_back("Target retirement year " + std::to_string(requested_retirement_year) +
                                   " is in the future, but hypothetical projection is DISABLED. Calculating for current year " +
                                   std::to_string(current_year) + ".");
                }
            }
            else
            {
                retirement_year = requested_retirement_year;
                is_hypothetical_mode = false;
            }

            // STAGE 1: BASE CALCULATION
            double zp = request->zp_macroeconomic_average();
            if (zp <= 0.0)
            {
                int macro_year = is_future_target ? current_year : retirement_year;
                zp = repo_.getMacroeconomicAverageSalary(macro_year);
            }

            if (zp <= 0.0)
            {
                res.success = false;
                res.error_message = "Macroeconomic average salary (Zp) is required and no average salary data exists in DB for prior years";
                return res;
            }

            SubsistenceLimits limits;
            if (request->has_subsistence_minimums() &&
                request->subsistence_minimums().for_disabled_persons() > 0.0 &&
                request->subsistence_minimums().general_minimum() > 0.0)
            {
                limits.for_disabled_persons = request->subsistence_minimums().for_disabled_persons();
                limits.general_minimum = request->subsistence_minimums().general_minimum();
            }
            else
            {
                int limits_year = is_future_target ? current_year : retirement_year;
                limits = repo_.getSubsistenceLimits(limits_year);
            }

            if (limits.for_disabled_persons <= 0.0 || limits.general_minimum <= 0.0)
            {
                res.success = false;
                res.error_message = "Missing subsistence minimum data in DB for target retirement year " + std::to_string(retirement_year);
                return res;
            }

            // 1. Calculate Ks (Service Coefficient)
            std::string months_error;
            int total_months = calculateTotalServiceMonths(request, retirement_year, is_hypothetical_mode, logs, months_error);
            if (total_months < 0)
            {
                res.success = false;
                res.error_message = months_error;
                return res;
            }
            double ks = (total_months * 1.0) / 1200.0;

            std::ostringstream ss1;
            ss1 << "Stage 1 [Service Ks]: Total insurance experience = " << total_months
                << " months. Ks = (" << total_months << " * 1) / 1200 = "
                << std::fixed << std::setprecision(4) << ks;
            logs.push_back(ss1.str());

            // 2. Calculate Kz (Wage Coefficient)
            std::string kz_error;
            double kz = calculateWageCoefficientKz(request, retirement_year, is_hypothetical_mode, logs, kz_error);
            if (kz < 0.0)
            {
                res.success = false;
                res.error_message = kz_error;
                return res;
            }

            double base_pension = zp * kz * ks;

            std::ostringstream ss2;
            ss2 << "Stage 1 [Base Pension P]: P = Zp (" << std::fixed << std::setprecision(2) << zp
                << ") * Kz (" << std::setprecision(4) << kz << ") * Ks (" << std::setprecision(4) << ks
                << ") = " << std::setprecision(2) << base_pension << " UAH";
            logs.push_back(ss2.str());

            // STAGE 2: PENSION TYPE MODIFIERS
            std::string modifier_error;
            double modifier = calculatePensionTypeModifier(request, logs, modifier_error);
            if (modifier < 0.0)
            {
                res.success = false;
                res.error_message = modifier_error;
                return res;
            }
            double modified_base = base_pension * modifier;

            // STAGE 3: EXTRA SERVICE ALLOWANCE
            double extra_allowance = calculateExtraServiceAllowance(request, total_months, base_pension, limits, logs);

            // STAGE 4: SPECIAL BENEFITS & AGE SURCHARGES
            std::vector<calc::BenefitType> active_benefits;
            for (int i = 0; i < request->benefits_size(); ++i)
            {
                if (request->benefits(i) != calc::BenefitType::AGE_SUPPLEMENT)
                {
                    active_benefits.push_back(request->benefits(i));
                }
            }

            auto surcharges = benefit_engine_.evaluateBenefits(active_benefits, limits);
            double total_surcharges = 0.0;

            for (const auto &sc : surcharges)
            {
                total_surcharges += sc.amount;
                std::ostringstream ss_sc;
                ss_sc << "Stage 4 [Benefit Surcharge]: " << sc.name << " = +"
                      << std::fixed << std::setprecision(2) << sc.amount << " UAH";
                logs.push_back(ss_sc.str());
            }

            double pre_age_pension = modified_base + extra_allowance + total_surcharges;
            SurchargeResult age_surcharge;
            double age_amount = calculateAgeSurcharge(request, pre_age_pension, limits, logs, age_surcharge);
            if (age_amount > 0.0)
            {
                surcharges.push_back(age_surcharge);
                total_surcharges += age_amount;
            }

            // STAGE 5: LEGAL CAPS (MIN / MAX BOUNDS)
            double pre_clamped = modified_base + extra_allowance + total_surcharges;
            double min_limit = limits.for_disabled_persons;
            double max_limit = 10.0 * limits.for_disabled_persons;

            double final_pension = pre_clamped;
            bool is_min_clamped = false;
            bool is_max_clamped = false;

            if (final_pension < min_limit)
            {
                final_pension = min_limit;
                is_min_clamped = true;
                std::ostringstream ss_min;
                ss_min << "Stage 5 [Minimum Limit Clamped]: Pre-clamped amount (" << std::fixed << std::setprecision(2)
                       << pre_clamped << " UAH) was below minimum subsistence limit (" << min_limit << " UAH). Clamped to minimum.";
                logs.push_back(ss_min.str());
            }
            else if (final_pension > max_limit)
            {
                final_pension = max_limit;
                is_max_clamped = true;
                std::ostringstream ss_max;
                ss_max << "Stage 5 [Maximum Limit Clamped]: Pre-clamped amount (" << std::fixed << std::setprecision(2)
                       << pre_clamped << " UAH) exceeded maximum legal limit (" << max_limit << " UAH). Clamped to maximum.";
                logs.push_back(ss_max.str());
            }
            else
            {
                std::ostringstream ss_ok;
                ss_ok << "Stage 5 [Legal Bounds Passed]: Final Pension = " << std::fixed << std::setprecision(2)
                      << final_pension << " UAH (within min " << min_limit << " - max " << max_limit << ")";
                logs.push_back(ss_ok.str());
            }

            // POPULATE RESULTS
            res.success = true;
            res.final_pension = final_pension;
            res.base_pension = base_pension;
            res.zp_macroeconomic_average = zp;
            res.kz_wage_coefficient = kz;
            res.ks_service_coefficient = ks;
            res.total_service_months = total_months;
            res.pension_type_modifier = modifier;
            res.extra_service_allowance = extra_allowance;
            res.total_benefit_surcharges = total_surcharges;
            res.pre_clamped_pension = pre_clamped;
            res.is_minimum_clamped = is_min_clamped;
            res.is_maximum_clamped = is_max_clamped;

            int client_age = calculateAgeInYears(request->date_of_birth(), request->retirement_date());
            bool criteria_met = (requested_retirement_year <= current_year) && (client_age >= 60) && (total_months >= 420);
            res.criteria_met = criteria_met;

            if (is_hypothetical_mode)
            {
                res.is_hypothetical = true;
                std::ostringstream ss_hypo;
                ss_hypo << "Notice: Theoretical (projected) pension calculation for target retirement year "
                        << requested_retirement_year << ". Statutory requirements not yet met. Assumptions: "
                        << "1) Continuous employment at current salary level; "
                        << "2) Latest PFU national average baseline Zp (" << std::fixed << std::setprecision(2) << zp << " UAH); "
                        << "3) Unadjusted for future inflation or statutory indexation.";
                res.hypothetical_disclaimer = ss_hypo.str();
                logs.push_back("Theoretical Projection: Calculation flagged as hypothetical for target retirement year " + std::to_string(requested_retirement_year));
            }
            else
            {
                res.is_hypothetical = false;
                res.hypothetical_disclaimer = "";
            }

            res.applied_benefits = surcharges;
            res.calculation_logs = logs;
            res.error_message = "";

            res.estimated_monthly_pension = final_pension;
            res.total_accumulated_capital = base_pension * 12.0 * 20.0;
            res.breakdown = logs.empty() ? "" : logs.back();

            return res;
        }

        CalculationResult PensionCalculator::calculateLegacy(const calc::PensionRequest *request)
        {
            calc::CalculatePensionRequest new_req;
            new_req.set_customer_id(request->customer_id());
            new_req.set_birth_year(request->birth_year());
            new_req.set_target_retirement_year(request->target_retirement_year());

            for (const auto &item : request->history())
            {
                auto *new_item = new_req.add_history();
                new_item->set_year(item.year());
                new_item->set_annual_income(item.annual_income());
                new_item->set_tax_paid(item.tax_paid());
                new_item->set_months_worked(item.months_worked());
            }

            return calculate(&new_req);
        }

    }
}