#include "pension_calculator.h"
#include "stages/service_coefficient_stage.h"
#include "stages/wage_coefficient_stage.h"
#include "stages/base_pension_stage.h"
#include "stages/pension_type_stage.h"
#include "stages/extra_service_stage.h"
#include "stages/benefit_surcharge_stage.h"
#include "stages/age_surcharge_stage.h"
#include "stages/legal_bounds_stage.h"
#include "util/date_utils.h"
#include "util/money_format.h"
#include <sstream>
#include <exception>

namespace calc
{
    namespace service
    {
        namespace
        {
            std::vector<std::unique_ptr<IPensionCalculationStage>> buildDefaultPipeline(
                const repository::CoefficientRepository &repo, const BenefitRulesEngine &engine)
            {
                std::vector<std::unique_ptr<IPensionCalculationStage>> stages;
                stages.push_back(std::make_unique<ServiceCoefficientStage>());
                stages.push_back(std::make_unique<WageCoefficientStage>(repo));
                stages.push_back(std::make_unique<BasePensionStage>());
                stages.push_back(std::make_unique<PensionTypeStage>());
                stages.push_back(std::make_unique<ExtraServiceStage>());
                stages.push_back(std::make_unique<BenefitSurchargeStage>(engine));
                stages.push_back(std::make_unique<AgeSurchargeStage>());
                stages.push_back(std::make_unique<LegalBoundsStage>());
                return stages;
            }
        }

        PensionCalculator::PensionCalculator(repository::CoefficientRepository repo)
            : repo_(std::move(repo)),
              benefit_engine_(),
              clock_(std::make_unique<SystemClock>()),
              logger_(std::make_unique<ConsoleLogger>()),
              stages_(buildDefaultPipeline(repo_, benefit_engine_))
        {
        }

        PensionCalculator::PensionCalculator(repository::CoefficientRepository repo,
                                              std::unique_ptr<IClock> clock,
                                              std::unique_ptr<ILogger> logger,
                                              std::vector<std::unique_ptr<IPensionCalculationStage>> stages)
            : repo_(std::move(repo)),
              benefit_engine_(),
              clock_(std::move(clock)),
              logger_(std::move(logger)),
              stages_(std::move(stages))
        {
        }

        bool PensionCalculator::resolveRetirementYear(const calc::CalculatePensionRequest *request,
                                                       int &retirement_year, bool &is_hypothetical_mode,
                                                       int &requested_retirement_year, std::string &error) const
        {
            int current_year = clock_->currentYear();

            if (request->retirement_date().length() >= 4)
            {
                auto ym = util::parseYearMonth(request->retirement_date());
                if (ym.valid)
                {
                    requested_retirement_year = ym.year;
                }
                else
                {
                    try
                    {
                        requested_retirement_year = std::stoi(request->retirement_date().substr(0, 4));
                    }
                    catch (const std::exception &)
                    {
                        error = "retirement_date is not a valid date: '" + request->retirement_date() + "'";
                        return false;
                    }
                }
            }
            else if (request->target_retirement_year() > 0)
            {
                requested_retirement_year = request->target_retirement_year();
            }
            else
            {
                error = "Either retirement_date or target_retirement_year is required to determine the applicable subsistence minimums";
                return false;
            }

            bool is_future_target = requested_retirement_year > current_year;
            bool enable_hypo_flag = request->enable_hypothetical_projection();

            if (is_future_target && enable_hypo_flag)
            {
                retirement_year = requested_retirement_year;
                is_hypothetical_mode = true;
            }
            else if (is_future_target)
            {
                retirement_year = current_year;
                is_hypothetical_mode = false;
            }
            else
            {
                retirement_year = requested_retirement_year;
                is_hypothetical_mode = false;
            }

            return true;
        }

        CalculationResult PensionCalculator::calculate(const calc::CalculatePensionRequest *request)
        {
            CalculationResult res;
            PensionCalculationContext ctx;
            ctx.request = request;
            ctx.current_year = clock_->currentYear();

            logger_->info("Executing 5-Stage Pension Calculation for Customer: " + request->customer_id());
            ctx.logs.push_back("Starting Pension Calculation Pipeline for Customer: " + request->customer_id());

            int requested_retirement_year = 0;
            std::string resolve_error;
            if (!resolveRetirementYear(request, ctx.retirement_year, ctx.is_hypothetical_mode,
                                        requested_retirement_year, resolve_error))
            {
                res.success = false;
                res.error_message = resolve_error;
                return res;
            }

            bool is_future_target = requested_retirement_year > ctx.current_year;

            if (ctx.is_hypothetical_mode)
            {
                ctx.logs.push_back("Target retirement year " + std::to_string(requested_retirement_year) +
                                    " is in the future and hypothetical projection is ENABLED.");
            }
            else if (is_future_target)
            {
                ctx.logs.push_back("Target retirement year " + std::to_string(requested_retirement_year) +
                                    " is in the future, but hypothetical projection is DISABLED. Calculating for current year " +
                                    std::to_string(ctx.current_year) + ".");
            }

            double zp = request->zp_macroeconomic_average();
            if (zp <= 0.0)
            {
                int macro_year = is_future_target ? ctx.current_year : ctx.retirement_year;
                zp = repo_.getMacroeconomicAverageSalary(macro_year);
            }
            if (zp <= 0.0)
            {
                res.success = false;
                res.error_message = "Macroeconomic average salary (Zp) is required and no average salary data exists in DB for prior years";
                return res;
            }
            ctx.zp_macroeconomic_average = zp;

            if (request->has_subsistence_minimums() &&
                request->subsistence_minimums().for_disabled_persons() > 0.0 &&
                request->subsistence_minimums().general_minimum() > 0.0)
            {
                ctx.limits.for_disabled_persons = request->subsistence_minimums().for_disabled_persons();
                ctx.limits.general_minimum = request->subsistence_minimums().general_minimum();
            }
            else
            {
                int limits_year = is_future_target ? ctx.current_year : ctx.retirement_year;
                ctx.limits = repo_.getSubsistenceLimits(limits_year);
            }

            if (ctx.limits.for_disabled_persons <= 0.0 || ctx.limits.general_minimum <= 0.0)
            {
                res.success = false;
                res.error_message = "Missing subsistence minimum data in DB for target retirement year " +
                                     std::to_string(ctx.retirement_year);
                return res;
            }

            for (const auto &stage : stages_)
            {
                std::string stage_error;
                if (!stage->execute(ctx, stage_error))
                {
                    res.success = false;
                    res.error_message = stage_error;
                    res.calculation_logs = ctx.logs;
                    return res;
                }
            }

            res.success = true;
            res.final_pension = ctx.final_pension;
            res.base_pension = ctx.base_pension;
            res.zp_macroeconomic_average = ctx.zp_macroeconomic_average;
            res.kz_wage_coefficient = ctx.kz_wage_coefficient;
            res.ks_service_coefficient = ctx.ks_service_coefficient;
            res.total_service_months = ctx.total_service_months;
            res.pension_type_modifier = ctx.pension_type_modifier;
            res.extra_service_allowance = ctx.extra_service_allowance;
            res.total_benefit_surcharges = ctx.total_benefit_surcharges;
            res.pre_clamped_pension = ctx.pre_clamped_pension;
            res.is_minimum_clamped = ctx.is_minimum_clamped;
            res.is_maximum_clamped = ctx.is_maximum_clamped;

            int client_age = util::ageInYears(request->date_of_birth(), request->retirement_date());
            res.criteria_met = (requested_retirement_year <= ctx.current_year) &&
                                (client_age >= 60) &&
                                (ctx.total_service_months >= 420);

            res.is_hypothetical = ctx.is_hypothetical_mode;
            if (ctx.is_hypothetical_mode)
            {
                std::ostringstream ss;
                ss << "Notice: Theoretical (projected) pension calculation for target retirement year "
                   << requested_retirement_year << ". Statutory requirements not yet met. Assumptions: "
                   << "1) Continuous employment at current salary level; "
                   << "2) Latest PFU national average baseline Zp (" << util::formatUah(ctx.zp_macroeconomic_average) << " UAH); "
                   << "3) Unadjusted for future inflation or statutory indexation.";
                res.hypothetical_disclaimer = ss.str();
                ctx.logs.push_back("Theoretical Projection: Calculation flagged as hypothetical for target retirement year " +
                                    std::to_string(requested_retirement_year));
            }
            else
            {
                res.hypothetical_disclaimer = "";
            }

            res.applied_benefits = ctx.applied_benefits;
            res.calculation_logs = ctx.logs;
            res.error_message = "";
            res.estimated_monthly_pension = ctx.final_pension;
            res.total_accumulated_capital = ctx.base_pension * 12.0 * 20.0;
            res.breakdown = ctx.logs.empty() ? "" : ctx.logs.back();

            return res;
        }

        CalculationResult PensionCalculator::calculateLegacy(const calc::PensionRequest *request)
        {
            auto new_req = legacy_adapter_.adapt(*request);
            return calculate(&new_req);
        }
    }
}