#ifndef PENSION_MODELS_H
#define PENSION_MODELS_H

#include <string>
#include <vector>
#include <memory>
#include "calc.pb.h"

namespace calc
{
    namespace service
    {

        struct SubsistenceLimits
        {
            double for_disabled_persons{0.0};
            double general_minimum{0.0};
            double age_surcharge_cap{10340.35};
        };

        struct SurchargeResult
        {
            calc::BenefitType type;
            std::string name;
            double amount;
        };

        class IBenefitStrategy
        {
        public:
            virtual ~IBenefitStrategy() = default;
            virtual calc::BenefitType getType() const = 0;
            virtual std::string getName() const = 0;
            virtual double calculateSurcharge(const SubsistenceLimits &limits) const = 0;
        };

        class CombatVeteranBenefitStrategy : public IBenefitStrategy
        {
        public:
            calc::BenefitType getType() const override { return calc::BenefitType::COMBAT_VETERAN; }
            std::string getName() const override { return "Combat Veteran (UBD) [+25% subsistence minimum]"; }
            double calculateSurcharge(const SubsistenceLimits &limits) const override
            {
                return limits.for_disabled_persons * 0.25;
            }
        };

        class HonoraryDonorBenefitStrategy : public IBenefitStrategy
        {
        public:
            calc::BenefitType getType() const override { return calc::BenefitType::HONORARY_DONOR; }
            std::string getName() const override { return "Honorary Donor of Ukraine [+10% general subsistence minimum]"; }
            double calculateSurcharge(const SubsistenceLimits &limits) const override
            {
                return limits.general_minimum * 0.10;
            }
        };

        class ChornobylLiquidatorBenefitStrategy : public IBenefitStrategy
        {
        public:
            calc::BenefitType getType() const override { return calc::BenefitType::CHORNOBYL_LIQUIDATOR; }
            std::string getName() const override { return "Chornobyl Liquidator [+30% subsistence minimum]"; }
            double calculateSurcharge(const SubsistenceLimits &limits) const override
            {
                return limits.for_disabled_persons * 0.30;
            }
        };

        class DisabledChildCareBenefitStrategy : public IBenefitStrategy
        {
        public:
            calc::BenefitType getType() const override { return calc::BenefitType::DISABLED_CHILD_CARE; }
            std::string getName() const override { return "Child Care for Child with Disability [+10% subsistence minimum]"; }
            double calculateSurcharge(const SubsistenceLimits &limits) const override
            {
                return limits.for_disabled_persons * 0.10;
            }
        };

        class BenefitRulesEngine
        {
        private:
            std::vector<std::unique_ptr<IBenefitStrategy>> strategies_;

        public:
            BenefitRulesEngine()
            {
                strategies_.push_back(std::make_unique<CombatVeteranBenefitStrategy>());
                strategies_.push_back(std::make_unique<HonoraryDonorBenefitStrategy>());
                strategies_.push_back(std::make_unique<ChornobylLiquidatorBenefitStrategy>());
                strategies_.push_back(std::make_unique<DisabledChildCareBenefitStrategy>());
            }

            std::vector<SurchargeResult> evaluateBenefits(
                const std::vector<calc::BenefitType> &activeBenefits,
                const SubsistenceLimits &limits) const
            {
                std::vector<SurchargeResult> results;
                for (const auto benefit : activeBenefits)
                {
                    for (const auto &strategy : strategies_)
                    {
                        if (strategy->getType() == benefit)
                        {
                            double amount = strategy->calculateSurcharge(limits);
                            results.push_back({benefit, strategy->getName(), amount});
                            break;
                        }
                    }
                }
                return results;
            }
        };

    }
}

#endif
