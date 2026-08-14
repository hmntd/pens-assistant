<?php

namespace App\Services;

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

        // Gender
        $genderStr = strtoupper($data['gender'] ?? 'MALE');
        $genderVal = match ($genderStr) {
            'FEMALE' => Gender::FEMALE,
            default => Gender::MALE,
        };
        $request->setGender($genderVal);

        $request->setDateOfBirth($data['date_of_birth'] ?? '1960-01-01');
        $request->setRetirementDate($data['retirement_date'] ?? '2024-01-01');

        // Pension Type
        $pensionTypeStr = strtoupper($data['pension_type'] ?? 'OLD_AGE');
        $pensionTypeVal = match ($pensionTypeStr) {
            'DISABILITY' => PensionType::DISABILITY,
            'LOSS_OF_BREADWINNER' => PensionType::LOSS_OF_BREADWINNER,
            default => PensionType::OLD_AGE,
        };
        $request->setPensionType($pensionTypeVal);

        // Disability Group
        $disabilityGroupStr = strtoupper($data['disability_group'] ?? 'DISABILITY_NONE');
        $disabilityGroupVal = match ($disabilityGroupStr) {
            'GROUP_1', '1' => DisabilityGroup::GROUP_1,
            'GROUP_2', '2' => DisabilityGroup::GROUP_2,
            'GROUP_3', '3' => DisabilityGroup::GROUP_3,
            default => DisabilityGroup::DISABILITY_NONE,
        };
        $request->setDisabilityGroup($disabilityGroupVal);

        $request->setDependentsCount((int) ($data['dependents_count'] ?? 0));
        $request->setEnableOptimizationRule((bool) ($data['enable_optimization_rule'] ?? true));

        if (!empty($data['zp_macroeconomic_average'])) {
            $request->setZpMacroeconomicAverage((float) $data['zp_macroeconomic_average']);
        }

        // Employment History
        if (!empty($data['employment_history']) && is_array($data['employment_history'])) {
            $employmentPeriods = [];
            foreach ($data['employment_history'] as $period) {
                $ep = new EmploymentPeriod();
                $ep->setStartDate($period['start_date']);
                $ep->setEndDate($period['end_date']);
                $ep->setMultiplier((float) ($period['multiplier'] ?? 1.0));
                $employmentPeriods[] = $ep;
            }
            $request->setEmploymentHistory($employmentPeriods);
        }

        // Salary History
        if (!empty($data['salary_history']) && is_array($data['salary_history'])) {
            $salaryRecords = [];
            foreach ($data['salary_history'] as $sal) {
                $sr = new SalaryMonthRecord();
                $sr->setYear((int) $sal['year']);
                $sr->setMonth((int) $sal['month']);
                $sr->setAmount((float) $sal['amount']);
                $salaryRecords[] = $sr;
            }
            $request->setSalaryHistory($salaryRecords);
        }

        // Benefits
        if (!empty($data['benefits']) && is_array($data['benefits'])) {
            $benefitEnums = [];
            foreach ($data['benefits'] as $b) {
                $bStr = strtoupper($b);
                $bEnum = match ($bStr) {
                    'COMBAT_VETERAN' => BenefitType::COMBAT_VETERAN,
                    'HONORARY_DONOR' => BenefitType::HONORARY_DONOR,
                    'CHORNOBYL_LIQUIDATOR' => BenefitType::CHORNOBYL_LIQUIDATOR,
                    'DISABLED_CHILD_CARE' => BenefitType::DISABLED_CHILD_CARE,
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

        // Save to Database
        return CalculatedPension::create([
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
            ],
        ]);
    }
}
