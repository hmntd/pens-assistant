<?php

namespace App\Http\Controllers;

use App\Models\CalculatedPension;
use App\Models\TaxHistory;
use Calc\CalcServiceClient;
use Calc\GetAverageSalariesRequest;
use Grpc\ChannelCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PensionCalculationBreakdownController extends Controller
{
    private array $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    public function __invoke(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $calc = CalculatedPension::where('id', $id)
            ->where(function ($query) use ($user) {
                if (!$user->is_admin) {
                    $query->where('user_id', $user->id);
                }
            })
            ->firstOrFail();

        $taxHistories = TaxHistory::where('user_id', $calc->user_id)
            ->orderBy('year', 'asc')
            ->get();

        $years = $taxHistories->pluck('year')->unique()->values()->all();

        // Query gRPC calc service for monthly PFU average salaries for all user years
        $nationalSalariesMap = [];
        try {
            $client = new CalcServiceClient('calc:50051', [
                'credentials' => ChannelCredentials::createInsecure(),
            ]);

            $grpcReq = new GetAverageSalariesRequest();
            if (!empty($years)) {
                $grpcReq->setYears($years);
            }

            /** @var \Calc\GetAverageSalariesResponse|null $grpcRes */
            list($grpcRes, $status) = $client->GetAverageSalaries($grpcReq)->wait();

            if ($status->code === \Grpc\STATUS_OK && $grpcRes && $grpcRes->getSuccess()) {
                foreach ($grpcRes->getSalaries() as $rec) {
                    $y = (int) $rec->getYear();
                    $m = (int) $rec->getMonth();
                    $amt = (float) $rec->getAmount();
                    $nationalSalariesMap[$y][$m] = $amt;
                }
            }
        } catch (\Throwable $e) {
            // Log fallback if gRPC is unavailable
            \Log::warning('Failed to fetch detailed average salaries via gRPC: ' . $e->getMessage());
        }

        $yearsData = [];

        foreach ($taxHistories as $th) {
            $year = (int) $th->year;
            $monthsWorked = max(1, min(12, (int) ($th->months_worked ?: 12)));
            $annualIncome = (float) $th->annual_income;
            $breakdown = $th->monthly_breakdown ?: [];
            $fallbackMonthly = $annualIncome > 0 ? ($annualIncome / $monthsWorked) : 0.0;

            $monthsList = [];
            $workedNationalSum = 0.0;
            $workedCoeffSum = 0.0;
            $workedMonthsCount = 0;

            $workedMonthIndexes = [];
            for ($m = 1; $m <= 12; $m++) {
                $sal = isset($breakdown[$m]) && is_numeric($breakdown[$m])
                    ? (float) $breakdown[$m]
                    : (isset($breakdown[(string) $m]) && is_numeric($breakdown[(string) $m])
                        ? (float) $breakdown[(string) $m]
                        : 0.0);
                if ($sal > 0) {
                    $workedMonthIndexes[] = $m;
                }
            }

            if (empty($workedMonthIndexes)) {
                for ($m = 1; $m <= $monthsWorked; $m++) {
                    $workedMonthIndexes[] = $m;
                }
            }

            foreach ($workedMonthIndexes as $m) {
                $userSalary = isset($breakdown[$m]) && is_numeric($breakdown[$m])
                    ? (float) $breakdown[$m]
                    : (isset($breakdown[(string) $m]) && is_numeric($breakdown[(string) $m])
                        ? (float) $breakdown[(string) $m]
                        : $fallbackMonthly);

                // Fetch month-specific national average salary from database with fallbacks
                $natSalary = $nationalSalariesMap[$year][$m] ?? $nationalSalariesMap[$year][0] ?? 0.0;
                if ($natSalary <= 0.0) {
                    // Fallback to nearest prior published month in the same year
                    for ($checkMonth = $m - 1; $checkMonth >= 1; $checkMonth--) {
                        if (!empty($nationalSalariesMap[$year][$checkMonth]) && $nationalSalariesMap[$year][$checkMonth] > 0) {
                            $natSalary = $nationalSalariesMap[$year][$checkMonth];
                            break;
                        }
                    }
                }
                if ($natSalary <= 0.0) {
                    // Fallback to macroeconomic average Zp or default
                    $natSalary = $calc->zp_macroeconomic_average > 0 ? (float) $calc->zp_macroeconomic_average : 16500.0;
                }

                $coeff = ($userSalary > 0 && $natSalary > 0) ? ($userSalary / $natSalary) : 0.0;

                $workedNationalSum += $natSalary;
                $workedCoeffSum += $coeff;
                $workedMonthsCount++;

                $monthsList[] = [
                    'month' => $m,
                    'month_name' => $this->monthNames[$m] ?? "Month {$m}",
                    'user_salary' => round($userSalary, 2),
                    'national_avg_salary' => round($natSalary, 2),
                    'monthly_coefficient' => round($coeff, 4),
                    'is_worked' => true,
                ];
            }

            $avgUserMonthlySalary = $workedMonthsCount > 0 ? ($annualIncome / $workedMonthsCount) : 0.0;
            $avgNationalSalary = $workedMonthsCount > 0 ? ($workedNationalSum / $workedMonthsCount) : 16500.0;
            $yearlyCoeff = $workedMonthsCount > 0 ? ($workedCoeffSum / $workedMonthsCount) : 0.0;

            $yearsData[] = [
                'year' => $year,
                'months_worked' => $workedMonthsCount,
                'user_annual_income' => round($annualIncome, 2),
                'user_avg_monthly_salary' => round($avgUserMonthlySalary, 2),
                'national_avg_salary' => round($avgNationalSalary, 2),
                'yearly_coefficient' => round($yearlyCoeff, 4),
                'months' => $monthsList,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $yearsData,
            'total_average_kz' => (float) ($calc->kz_wage_coefficient ?: 1.0),
        ], Response::HTTP_OK);
    }
}
