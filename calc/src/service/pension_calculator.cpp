#include "pension_calculator.h"
#include <iostream>
#include <cmath>
#include <algorithm>
#include <numeric>
#include <sstream>
#include <iomanip>
#include <stdexcept>

namespace calc
{
    namespace service
    {

        PensionCalculator::PensionCalculator(repository::CoefficientRepository repo)
            : repo_(std::move(repo)), benefit_engine_() {}

        int PensionCalculator::calculateTotalServiceMonths(
            const calc::CalculatePensionRequest *request,
            std::vector<std::string> &logs,
            std::string &error_message) const
        {
            int total_months = 0;

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
                }
            }
            else
            {
                error_message = "No employment_history or legacy history provided; cannot determine insurance experience";
                return -1;
            }

            return total_months;
        }

        double PensionCalculator::calculateWageCoefficientKz(
            const calc::CalculatePensionRequest *request,
            std::vector<std::string> &logs,
            std::string &error_message) const
        {
            std::vector<double> ratios;

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
                        error_message = "Salary history record for year " + std::to_string(rec.year()) +
                                        ", month " + std::to_string(rec.month()) + " has a non-positive amount";
                        return -1.0;
                    }

                    double ratio = rec.amount() / avg_national;
                    ratios.push_back(ratio);
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
                        error_message = "History record for year " + std::to_string(rec.year()) +
                                        " has a non-positive annual_income";
                        return -1.0;
                    }

                    double monthly_income = rec.annual_income() / rec.months_worked();
                    double ratio = monthly_income / avg_national;

                    for (int m = 0; m < rec.months_worked(); ++m)
                    {
                        ratios.push_back(ratio);
                    }
                }
            }
            else
            {
                error_message = "No salary_history or legacy history provided; cannot calculate wage coefficient Kz";
                return -1.0;
            }

            double initial_sum = std::accumulate(ratios.begin(), ratios.end(), 0.0);
            double initial_kz = initial_sum / ratios.size();

            if (request->enable_optimization_rule() && ratios.size() >= 10)
            {
                std::sort(ratios.begin(), ratios.end());
                size_t drop_count = ratios.size() * 0.10; // Drop worst 10%

                if (drop_count > 0 && drop_count < ratios.size())
                {
                    std::vector<double> optimized_ratios(ratios.begin() + drop_count, ratios.end());
                    double opt_sum = std::accumulate(optimized_ratios.begin(), optimized_ratios.end(), 0.0);
                    double opt_kz = opt_sum / optimized_ratios.size();

                    if (opt_kz > initial_kz)
                    {
                        std::ostringstream ss;
                        ss << "Optimization Rule Applied: Dropped worst " << drop_count
                           << " salary months. Kz improved from " << std::fixed << std::setprecision(4)
                           << initial_kz << " to " << opt_kz;
                        logs.push_back(ss.str());
                        return opt_kz;
                    }
                }
            }

            std::ostringstream ss;
            ss << "Wage coefficient Kz calculated over " << ratios.size() << " months: "
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
            int required_months = 420;
            int extra_months = total_months - required_months;

            if (extra_months <= 0)
            {
                logs.push_back("Extra Service Allowance: 0 UAH (service requirements met)");
                return 0.0;
            }

            int extra_full_years = extra_months / 12;
            if (extra_full_years <= 0)
            {
                return 0.0;
            }

            // 1% of Base Pension per extra full year, capped at 1% of subsistence minimum for disabled
            double percent_of_base = base_pension * 0.01;
            double max_allowed_per_year = limits.for_disabled_persons * 0.01;
            double allowance_per_year = std::min(percent_of_base, max_allowed_per_year);

            double total_allowance = allowance_per_year * extra_full_years;

            std::ostringstream ss;
            ss << "Extra Service Allowance: " << extra_full_years << " extra years over norm (35 yrs / 420 months). Allowance = "
               << std::fixed << std::setprecision(2) << total_allowance << " UAH";
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

            out_surcharge.type = calc::BenefitType::AGE_SUPPLEMENT;
            out_surcharge.name = bracket_name;
            out_surcharge.amount = amount;

            std::ostringstream ss;
            ss << "Stage 4 [Age Surcharge Applied]: " << bracket_name << " = +"
               << std::fixed << std::setprecision(2) << amount << " UAH";
            logs.push_back(ss.str());

            return amount;
        }

        CalculationResult PensionCalculator::calculate(const calc::CalculatePensionRequest *request)
        {
            CalculationResult res;
            std::vector<std::string> logs;

            std::cout << "[Calc Engine] Executing 5-Stage Pension Calculation for Customer: " << request->customer_id() << std::endl;
            logs.push_back("Starting Pension Calculation Pipeline for Customer: " + request->customer_id());

            // STAGE 1: BASE CALCULATION
            // Macroeconomic Average Zp must be provided and > 0.0
            double zp = request->zp_macroeconomic_average();
            if (zp <= 0.0)
            {
                res.success = false;
                res.error_message = "Macroeconomic average salary (Zp) is required and must be greater than 0.0";
                return res;
            }

            int retirement_year;
            if (request->retirement_date().length() >= 4)
            {
                try
                {
                    retirement_year = std::stoi(request->retirement_date().substr(0, 4));
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
                retirement_year = request->target_retirement_year();
            }
            else
            {
                res.success = false;
                res.error_message = "Either retirement_date or target_retirement_year is required to determine "
                                    "the applicable subsistence minimums";
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
                limits = repo_.getSubsistenceLimits(retirement_year);
            }

            if (limits.for_disabled_persons <= 0.0 || limits.general_minimum <= 0.0)
            {
                res.success = false;
                res.error_message = "Missing subsistence minimum data in DB for target retirement year " + std::to_string(retirement_year);
                return res;
            }

            // 1. Calculate Ks (Service Coefficient)
            std::string months_error;
            int total_months = calculateTotalServiceMonths(request, logs, months_error);
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
            double kz = calculateWageCoefficientKz(request, logs, kz_error);
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
            res.applied_benefits = surcharges;
            res.calculation_logs = logs;
            res.error_message = "";

            // Legacy compatibility fields
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