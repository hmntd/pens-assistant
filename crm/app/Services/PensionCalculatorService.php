<?php

namespace App\Services;

use App\Events\PensionCalculated;
use App\Models\CalculatedPension;
use App\Models\User;
use Calc\BenefitType;
use Calc\CalcServiceClient;
use Calc\CalculatePensionRequest;
use Calc\CalculatePensionResponse;
use Calc\DisabilityGroup;
use Calc\EmploymentPeriod;
use Calc\Gender;
use Calc\PensionType;
use Calc\SalaryMonthRecord;
use Calc\SubsistenceMinimums;
use Calc\TaxRecord;
use Grpc\ChannelCredentials;
use Illuminate\Support\Facades\Log;

class PensionCalculatorService
{
    protected string $grpcHost;

    public function __construct(?string $grpcHost = null)
    {
        $this->grpcHost = $grpcHost ?? config('services.calc.host', 'calc:50051');
    }

    /**
     * Calculate pension via C++ gRPC engine and save to database.
     *
     * @param User $user Target user for calculation
     * @param array $data Validated input payload
     * @return CalculatedPension
     * @throws \RuntimeException
     */
    public function calculateAndSave(User $user, array $data): CalculatedPension
    {
        $client = new CalcServiceClient($this->grpcHost, [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);

        $request = new CalculatePensionRequest();
        $request->setCustomerId((string) $user->id);

        // Gender: input override -> user profile
        $rawGender = $data['gender'] ?? $user->gender;
        if (empty($rawGender)) {
            throw new \InvalidArgumentException('Gender is not specified.');
        }
        $genderStr = strtoupper((string) $rawGender);
        $genderVal = match ($genderStr) {
            'FEMALE' => Gender::FEMALE,
            'MALE' => Gender::MALE,
            default => throw new \InvalidArgumentException('Invalid gender.'),
        };
        $request->setGender($genderVal);

        // Date of Birth: input override -> user profile
        $userDob = $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : null;
        $dobStr = (string) ($data['date_of_birth'] ?? $userDob);
        if (empty($dobStr)) {
            throw new \InvalidArgumentException('Date of birth is not specified.');
        }
        $request->setDateOfBirth($dobStr);

        $enableHypothetical = (bool) ($data['enable_hypothetical_projection'] ?? false);
        $request->setEnableHypotheticalProjection($enableHypothetical);

        $currentYear = (int) date('Y');
        $rawRetirementYear = (int) ($data['target_retirement_year'] ?? $user->target_retirement_year ?? $currentYear);

        // If target_retirement_year is in the future and hypothetical projection is false,
        // force fallback of target_retirement_year to current_year
        if ($rawRetirementYear > $currentYear && ! $enableHypothetical) {
            $retirementYear = $currentYear;
        } else {
            $retirementYear = $rawRetirementYear;
        }

        $retirementDate = (string) ($data['retirement_date'] ?? "{$retirementYear}-01-01");
        $request->setRetirementDate($retirementDate);
        $request->setTargetRetirementYear($retirementYear);

        // Pension Type
        $pensionTypeStr = strtoupper((string) ($data['pension_type'] ?? 'OLD_AGE'));
        $pensionTypeVal = match ($pensionTypeStr) {
            'DISABILITY' => PensionType::DISABILITY,
            'LOSS_OF_BREADWINNER' => PensionType::LOSS_OF_BREADWINNER,
            default => PensionType::OLD_AGE,
        };
        $request->setPensionType($pensionTypeVal);

        // Disability Group: input override -> user profile -> default DISABILITY_NONE
        $rawDisability = $data['disability_group'] ?? $user->disability_group ?? 'DISABILITY_NONE';
        $disabilityGroupStr = strtoupper((string) $rawDisability);
        $disabilityGroupVal = match ($disabilityGroupStr) {
            'GROUP_1', '1' => DisabilityGroup::GROUP_1,
            'GROUP_2', '2' => DisabilityGroup::GROUP_2,
            'GROUP_3', '3' => DisabilityGroup::GROUP_3,
            default => DisabilityGroup::DISABILITY_NONE,
        };
        $request->setDisabilityGroup($disabilityGroupVal);

        $request->setDependentsCount((int) ($data['dependents_count'] ?? 0));
        $request->setEnableOptimizationRule((bool) ($data['enable_optimization_rule'] ?? true));

        // Zp Macroeconomic average salary (delegate to C++ engine unless explicit admin override is provided)
        if (!empty($data['zp_macroeconomic_average']) && (float) $data['zp_macroeconomic_average'] > 0.0) {
            $request->setZpMacroeconomicAverage((float) $data['zp_macroeconomic_average']);
        } else {
            $request->setZpMacroeconomicAverage(0.0);
        }

        // Fetch User Tax Histories once for auto-deriving employment, salary, and legacy history if missing
        $taxHistories = $user->taxHistories()->orderBy('year')->get();

        // Employment History
        $employmentPeriods = [];
        if (!empty($data['employment_history']) && is_array($data['employment_history'])) {
            foreach ($data['employment_history'] as $period) {
                $ep = new EmploymentPeriod();
                $ep->setStartDate($period['start_date']);
                $ep->setEndDate($period['end_date']);
                $ep->setMultiplier((float) ($period['multiplier'] ?? 1.0));
                $employmentPeriods[] = $ep;
            }
        } else {
            // Auto-generate employment periods from user's tax histories
            foreach ($taxHistories as $th) {
                /** @var \App\Models\TaxHistory $th */
                $months = max(1, min(12, (int) ($th->months_worked ?: 12)));
                $endMonthStr = str_pad((string) $months, 2, '0', STR_PAD_LEFT);
                $endDay = match ($endMonthStr) {
                    '02' => '28',
                    '04', '06', '09', '11' => '30',
                    default => '31',
                };
                $ep = new EmploymentPeriod();
                $ep->setStartDate("{$th->year}-01-01");
                $ep->setEndDate("{$th->year}-{$endMonthStr}-{$endDay}");
                $ep->setMultiplier(1.0);
                $employmentPeriods[] = $ep;
            }
        }
        if (!empty($employmentPeriods)) {
            $request->setEmploymentHistory($employmentPeriods);
        }

        // Salary History & Legacy Tax Records
        $salaryRecords = [];
        $legacyTaxRecords = [];

        if (!empty($data['salary_history']) && is_array($data['salary_history'])) {
            foreach ($data['salary_history'] as $sal) {
                $sr = new SalaryMonthRecord();
                $sr->setYear((int) $sal['year']);
                $sr->setMonth((int) $sal['month']);
                $sr->setAmount((float) $sal['amount']);
                if (isset($sal['is_special_period'])) {
                    $sr->setIsSpecialPeriod((bool) $sal['is_special_period']);
                }
                $salaryRecords[] = $sr;
            }
        } else {
            // Auto-load salary history from user tax histories
            foreach ($taxHistories as $th) {
                /** @var \App\Models\TaxHistory $th */
                $months = max(1, min(12, (int) ($th->months_worked ?: 12)));
                $breakdown = $th->monthly_breakdown ?: [];
                $fallbackMonthly = (float) $th->annual_income / $months;

                for ($m = 1; $m <= 12; $m++) {
                    $amount = isset($breakdown[$m]) && is_numeric($breakdown[$m])
                        ? (float) $breakdown[$m]
                        : (isset($breakdown[(string) $m]) && is_numeric($breakdown[(string) $m])
                            ? (float) $breakdown[(string) $m]
                            : ($m <= $months ? $fallbackMonthly : 0.0));

                    if ($amount > 0) {
                        $sr = new SalaryMonthRecord();
                        $sr->setYear((int) $th->year);
                        $sr->setMonth($m);
                        $sr->setAmount($amount);
                        $salaryRecords[] = $sr;
                    }
                }

                $tr = new TaxRecord();
                $tr->setYear((int) $th->year);
                $tr->setAnnualIncome((float) $th->annual_income);
                $tr->setTaxPaid((float) $th->tax_paid);
                $tr->setMonthsWorked($months);
                $legacyTaxRecords[] = $tr;
            }
        }

        $enableHypothetical = (bool) ($data['enable_hypothetical_projection'] ?? $data['is_hypothetical_projection'] ?? false);
        $request->setEnableHypotheticalProjection($enableHypothetical);

        $latestRecordedYear = 0;
        $latestMonthlySalary = 0.0;

        foreach ($salaryRecords as $sr) {
            /** @var SalaryMonthRecord $sr */
            if ($sr->getYear() > $latestRecordedYear) {
                $latestRecordedYear = $sr->getYear();
            }
        }

        if ($latestRecordedYear > 0) {
            foreach ($salaryRecords as $sr) {
                /** @var SalaryMonthRecord $sr */
                if ($sr->getYear() === $latestRecordedYear && $sr->getAmount() > 0) {
                    $latestMonthlySalary = $sr->getAmount();
                }
            }
        }

        if ($latestMonthlySalary <= 0.0 && $taxHistories->isNotEmpty()) {
            /** @var \App\Models\TaxHistory|null $latestTh */
            $latestTh = $taxHistories->sortByDesc('year')->first();
            if ($latestTh && $latestTh->annual_income > 0) {
                $latestRecordedYear = (int) $latestTh->year;
                $monthsWorked = max(1, (int) ($latestTh->months_worked ?: 12));
                $latestMonthlySalary = (float) $latestTh->annual_income / $monthsWorked;
            }
        }

        if ($enableHypothetical && $retirementYear > $latestRecordedYear && $latestMonthlySalary > 0) {
            for ($futureYear = $latestRecordedYear + 1; $futureYear <= $retirementYear; $futureYear++) {
                $ep = new EmploymentPeriod();
                $ep->setStartDate("{$futureYear}-01-01");
                $ep->setEndDate("{$futureYear}-12-31");
                $ep->setMultiplier(1.0);
                $employmentPeriods[] = $ep;

                for ($m = 1; $m <= 12; $m++) {
                    $sr = new SalaryMonthRecord();
                    $sr->setYear($futureYear);
                    $sr->setMonth($m);
                    $sr->setAmount($latestMonthlySalary);
                    $salaryRecords[] = $sr;
                }
            }
        }

        if (!empty($employmentPeriods)) {
            $request->setEmploymentHistory($employmentPeriods);
        }

        if (!empty($salaryRecords)) {
            $request->setSalaryHistory($salaryRecords);
        }

        if (!empty($legacyTaxRecords)) {
            $request->setHistory($legacyTaxRecords);
        }

        // Benefits: input override -> user profile -> empty array
        $rawBenefits = $data['benefits'] ?? $user->benefits ?? [];
        if (!empty($rawBenefits) && is_array($rawBenefits)) {
            $benefitEnums = [];
            foreach ($rawBenefits as $b) {
                $bStr = strtoupper((string) $b);
                $bEnum = match ($bStr) {
                    'COMBAT_VETERAN' => BenefitType::COMBAT_VETERAN,
                    'HONORARY_DONOR' => BenefitType::HONORARY_DONOR,
                    'CHORNOBYL_LIQUIDATOR' => BenefitType::CHORNOBYL_LIQUIDATOR,
                    'DISABLED_CHILD_CARE' => BenefitType::DISABLED_CHILD_CARE,
                    'AGE_SUPPLEMENT' => BenefitType::AGE_SUPPLEMENT,
                    default => null,
                };
                if ($bEnum !== null) {
                    $benefitEnums[] = $bEnum;
                }
            }
            $request->setBenefits($benefitEnums);
        }

        // Subsistence Minimums
        $subMin = new SubsistenceMinimums();
        $subMin->setForDisabledPersons(2361.0);
        $subMin->setGeneralMinimum(2920.0);
        $subMin->setAgeSurchargeCap(10340.35);
        $request->setSubsistenceMinimums($subMin);

        // Execute gRPC Call
        /** @var CalculatePensionResponse $response */
        list($response, $status) = $client->CalculatePension($request)->wait();

        if ($status->code !== \Grpc\STATUS_OK || !$response || !$response->getSuccess()) {
            $errMsg = $response ? $response->getErrorMessage() : ($status->details ?? 'gRPC connection failed');
            Log::error('Calc Engine gRPC Error', ['status' => $status, 'error' => $errMsg]);
            throw new \RuntimeException("Pension Calculation Engine Error: {$errMsg}");
        }

        // Parse Applied Benefits
        $appliedBenefits = [];
        foreach ($response->getAppliedBenefits() as $bDetail) {
            $appliedBenefits[] = [
                'benefit' => $bDetail->getBenefit(),
                'name' => $bDetail->getName(),
                'amount' => $bDetail->getAmount(),
            ];
        }

        $logs = iterator_to_array($response->getCalculationLogs());

        $isHypothetical = method_exists($response, 'getIsHypothetical') ? $response->getIsHypothetical() : false;
        $hypotheticalDisclaimer = method_exists($response, 'getHypotheticalDisclaimer') ? $response->getHypotheticalDisclaimer() : '';

        $userAge = $user->date_of_birth ? $user->date_of_birth->age : 0;
        $currentYear = (int) date('Y');
        $targetRetinementYear = (int) ($data['target_retirement_year'] ?? $user->target_retirement_year ?? $currentYear);
        $totalMonths = (int) $response->getTotalServiceMonths();
        $criteriaMet = ($targetRetinementYear <= $currentYear) && ($userAge >= 60) && ($totalMonths >= 420);

        $calculatedPension = CalculatedPension::create([
            'user_id' => $user->id,
            'final_pension' => $response->getFinalPension(),
            'base_pension' => $response->getBasePension(),
            'zp_macroeconomic_average' => $response->getZpMacroeconomicAverage(),
            'kz_wage_coefficient' => $response->getKzWageCoefficient(),
            'ks_service_coefficient' => $response->getKsServiceCoefficient(),
            'total_service_months' => $response->getTotalServiceMonths(),
            'pension_type' => strtolower($data['pension_type'] ?? 'old_age'),
            'disability_group' => strtolower($data['disability_group'] ?? 'none'),
            'input_parameters' => $data,
            'applied_benefits' => $appliedBenefits,
            'calculation_logs' => $logs,
            'estimated_monthly_pension' => $response->getFinalPension(),
            'total_accumulated_capital' => $response->getBasePension() * 12 * 20,
            'calculation_breakdown' => [
                'logs' => $logs,
                'pre_clamped' => $response->getPreClampedPension(),
                'is_min_clamped' => $response->getIsMinimumClamped(),
                'is_max_clamped' => $response->getIsMaximumClamped(),
                'is_hypothetical' => $isHypothetical,
                'criteria_met' => $criteriaMet,
                'hypothetical_disclaimer' => $hypotheticalDisclaimer,
            ],
        ]);

        event(new PensionCalculated($user, $calculatedPension));

        return $calculatedPension;
    }
}
