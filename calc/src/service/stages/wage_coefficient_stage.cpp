#include "wage_coefficient_stage.h"
#include "../util/date_utils.h"
#include "../util/money_format.h"
#include <algorithm>
#include <cmath>
#include <sstream>

namespace calc
{
    namespace service
    {
        namespace
        {
            struct MonthRatioItem
            {
                int year;
                int month;
                double ratio;
                bool is_special_period;
            };

            bool isStatutorySpecialPeriod(int year, int month)
            {
                return (year == 2020 && month >= 3) || year == 2021 || year == 2022 || year >= 2023;
            }
        }

        bool WageCoefficientStage::execute(PensionCalculationContext &ctx, std::string &error) const
        {
            const auto *request = ctx.request;
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
                        error = "Missing national average salary data in DB for year " +
                                std::to_string(rec.year()) + ", month " + std::to_string(rec.month());
                        return false;
                    }

                    if (rec.amount() <= 0.0)
                    {
                        continue;
                    }

                    double ratio = rec.amount() / avg_national;
                    bool is_special = rec.is_special_period() || isStatutorySpecialPeriod(rec.year(), rec.month());

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
                        error = "Missing national average salary data in DB for year " + std::to_string(rec.year());
                        return false;
                    }

                    if (rec.months_worked() <= 0)
                    {
                        error = "History record for year " + std::to_string(rec.year()) +
                                " is missing months_worked (must be > 0); cannot derive monthly income";
                        return false;
                    }

                    if (rec.annual_income() <= 0.0)
                    {
                        continue;
                    }

                    double monthly_income = rec.annual_income() / rec.months_worked();
                    double ratio = monthly_income / avg_national;
                    bool is_special = (rec.year() >= 2020);

                    for (int m = 1; m <= rec.months_worked(); ++m)
                    {
                        MonthRatioItem item{rec.year(), m, ratio, is_special};
                        if (rec.year() < 2000 || (rec.year() == 2000 && m <= 6))
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

            if (ctx.is_hypothetical_mode && ctx.retirement_year > ctx.current_year && last_monthly_income > 0.0)
            {
                double latest_national_avg = repo_.getAverageSalary(ctx.current_year, 1);
                if (latest_national_avg <= 0.0)
                {
                    latest_national_avg = 20000.0;
                }

                double proj_ratio = last_monthly_income / latest_national_avg;

                int target_end_month = 12;
                auto target = util::parseYearMonth(request->retirement_date());
                if (target.valid)
                {
                    target_end_month = target.month;
                }

                int curr_y = last_year;
                int curr_m = last_month + 1;
                if (curr_m > 12)
                {
                    curr_m = 1;
                    curr_y++;
                }

                int proj_count = 0;
                while (curr_y < ctx.retirement_year || (curr_y == ctx.retirement_year && curr_m <= target_end_month))
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
                    ctx.logs.push_back("Hypothetical Projection: Projected " + std::to_string(proj_count) +
                                        " future monthly salary records at last known income (" +
                                        std::to_string(static_cast<int>(last_monthly_income)) + " UAH/mo)");
                }
            }

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
                        ctx.logs.push_back(ss.str());
                    }
                    else
                    {
                        double window_sum = 0.0;
                        for (size_t j = 0; j < 60; ++j)
                        {
                            window_sum += raw_pre_2000_items[j].ratio;
                        }

                        size_t best_start = 0;
                        double max_window_sum = window_sum;

                        for (size_t i = 1; i <= raw_pre_2000_items.size() - 60; ++i)
                        {
                            window_sum += raw_pre_2000_items[i + 59].ratio - raw_pre_2000_items[i - 1].ratio;
                            if (window_sum > max_window_sum)
                            {
                                max_window_sum = window_sum;
                                best_start = i;
                            }
                        }

                        selected_pre_2000_items.assign(raw_pre_2000_items.begin() + best_start,
                                                        raw_pre_2000_items.begin() + best_start + 60);

                        std::ostringstream ss;
                        ss << "Stage 2 [Kz Art. 40 Law 1058-IV]: Selected optimal 60 consecutive pre-July 1, 2000 salary months (from "
                           << raw_pre_2000_items[best_start].year << "-"
                           << (raw_pre_2000_items[best_start].month < 10 ? "0" : "") << raw_pre_2000_items[best_start].month
                           << " to " << raw_pre_2000_items[best_start + 59].year << "-"
                           << (raw_pre_2000_items[best_start + 59].month < 10 ? "0" : "") << raw_pre_2000_items[best_start + 59].month
                           << ") out of " << raw_pre_2000_items.size() << " available pre-2000 months.";
                        ctx.logs.push_back(ss.str());
                    }
                }
                else
                {
                    std::ostringstream ss;
                    ss << "Stage 2 [Kz Art. 40 Law 1058-IV]: Pre-July 1, 2000 salary history omitted from Kz calculation "
                       << "(has " << raw_pre_2000_items.size() << " pre-2000 salary months; Art. 40 requires 60 consecutive months of salary history when post-2000 experience is >= 60 months). "
                       << "Pre-2000 period counted towards insurance service Ks.";
                    ctx.logs.push_back(ss.str());
                }
            }

            std::vector<MonthRatioItem> items;
            items.insert(items.end(), selected_pre_2000_items.begin(), selected_pre_2000_items.end());
            items.insert(items.end(), post_2000_items.begin(), post_2000_items.end());

            if (items.empty())
            {
                ctx.logs.push_back("Stage 2 [Kz Wage Coefficient]: Service-only history provided without salary entries. Defaulting Kz = 1.0000.");
                ctx.kz_wage_coefficient = 1.0;
                return true;
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
                    std::sort(sorted_items.begin(), sorted_items.end(),
                              [](const MonthRatioItem &a, const MonthRatioItem &b)
                              { return a.ratio < b.ratio; });

                    size_t total_dropped = 0, pct10_dropped = 0, special_dropped = 0;
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
                               << util::formatCoefficient(initial_kz) << " to " << util::formatCoefficient(opt_kz);
                            ctx.logs.push_back(ss.str());
                            ctx.kz_wage_coefficient = opt_kz;
                            return true;
                        }
                    }
                }
            }

            std::ostringstream ss;
            ss << "Wage coefficient Kz calculated over " << items.size() << " months: "
               << util::formatCoefficient(initial_kz);
            ctx.logs.push_back(ss.str());

            ctx.kz_wage_coefficient = initial_kz;
            return true;
        }
    }
}
