<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Pension Calculation #{{ $calculation->id }} | PensAssistant</title>
    <style>
        @page {
            margin: 25px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.5;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #31de97;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
        }
        .brand-subtitle {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-meta {
            text-align: right;
            font-size: 10px;
            color: #475569;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            border-left: 3px solid #31de97;
            padding-left: 8px;
            margin-top: 15px;
            margin-bottom: 8px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-grid td {
            padding: 6px 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .info-label {
            color: #64748b;
            font-size: 10px;
        }
        .info-val {
            font-weight: bold;
            color: #0f172a;
        }
        .summary-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .summary-card table {
            width: 100%;
            border-collapse: collapse;
        }
        .hero-price {
            font-size: 22px;
            font-weight: bold;
            color: #047857;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 10px;
            text-transform: uppercase;
            padding: 7px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 7px;
            border: 1px solid #e2e8f0;
            font-size: 10.5px;
        }
        .logs-box {
            background: #0f172a;
            color: #34d399;
            font-family: 'DejaVu Sans Mono', monospace, sans-serif;
            font-size: 9.5px;
            padding: 10px;
            border-radius: 4px;
            line-height: 1.4;
        }
        .footer {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td>
                <div class="brand-title">PensAssistant</div>
                <div class="brand-subtitle">Pension Calculation Report</div>
            </td>
            <td class="doc-meta">
                <strong>Calculation ID:</strong> #{{ $calculation->id }}<br>
                <strong>Generated Date:</strong> {{ now()->format('Y-m-d H:i') }}<br>
                <strong>C++ Engine Version:</strong> 2.4.0
            </td>
        </tr>
    </table>

    <!-- User Information -->
    <div class="section-title">1. User Profile & Calculation Parameters</div>
    <table class="info-grid">
        <tr>
            <td width="50%">
                <div class="info-label">Full Name:</div>
                <div class="info-val">{{ $user->name }}</div>
            </td>
            <td width="50%">
                <div class="info-label">Email Address:</div>
                <div class="info-val">{{ $user->email }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="info-label">Gender:</div>
                <div class="info-val">{{ $user->gender === 'FEMALE' ? 'Female' : ($user->gender === 'MALE' ? 'Male' : 'Not specified') }}</div>
            </td>
            <td>
                <div class="info-label">Date of Birth:</div>
                <div class="info-val">{{ $user->date_of_birth ?? 'Not specified' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="info-label">Disability Group:</div>
                <div class="info-val">{{ $user->disability_group ?? 'None' }}</div>
            </td>
            <td>
                <div class="info-label">Target Retirement Year:</div>
                <div class="info-val">{{ $calculation->input_parameters['target_retirement_year'] ?? $calculation->target_retirement_year ?? date('Y') }}</div>
            </td>
        </tr>
    </table>

    <!-- Summary Box -->
    <div class="summary-card">
        <table>
            <tr>
                <td>
                    <div style="font-size: 11px; color: #065f46; font-weight: bold;">ESTIMATED FINAL PENSION AMOUNT</div>
                    <div class="hero-price">{{ number_format((float)($calculation->final_pension ?? $calculation->final_pension_amount ?? 0), 2, '.', ' ') }} UAH</div>
                </td>
                <td style="text-align: right; font-size: 10px; color: #047857;">
                    Base Pension: <strong>{{ number_format((float)($calculation->base_pension ?? $calculation->base_pension_amount ?? 0), 2, '.', ' ') }} UAH</strong><br>
                    Insurance Service: <strong>{{ floor(($calculation->total_service_months ?? 0) / 12) }} yrs {{ ($calculation->total_service_months ?? 0) % 12 }} mos</strong> ({{ $calculation->total_service_months ?? 0 }} mos)
                </td>
            </tr>
        </table>
    </div>

    <!-- Coefficients & Formula Breakdown -->
    <div class="section-title">2. Coefficients & Calculation Formula</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Indicator / Coefficient</th>
                <th>Calculated Value</th>
                <th>Description & Formula (Law No. 1058-IV)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Kz (Wage Ratio Coefficient)</strong></td>
                <td><strong>{{ number_format((float)($calculation->kz_wage_coefficient ?? 1.0), 4) }}</strong></td>
                <td>Average ratio of monthly income to national average wage in Ukraine</td>
            </tr>
            <tr>
                <td><strong>Zp (National Average Salary)</strong></td>
                <td><strong>{{ number_format((float)($calculation->zp_macroeconomic_average ?? 16500), 2, '.', ' ') }} UAH</strong></td>
                <td>Macroeconomic 3-year average monthly salary preceding the application year</td>
            </tr>
            <tr>
                <td><strong>Ks (Insurance Service Multiplier)</strong></td>
                <td><strong>{{ number_format((float)($calculation->ks_service_coefficient ?? 0.35), 4) }}</strong></td>
                <td>Formula: (Service Months * 1%) / 1200. Weighted multiplier assessing service duration</td>
            </tr>
            <tr>
                <td><strong>Old-Age Base Pension</strong></td>
                <td><strong>{{ number_format((float)($calculation->base_pension ?? $calculation->base_pension_amount ?? 0), 2, '.', ' ') }} UAH</strong></td>
                <td>Formula: P_base = Zp * Kz * Ks</td>
            </tr>
        </tbody>
    </table>

    <!-- C++ Execution Audit Logs (if present) -->
    @if(!empty($calculation_logs) && count($calculation_logs) > 0)
    <div class="section-title">3. C++ Execution Audit Trail</div>
    <div class="logs-box">
        @foreach($calculation_logs as $logLine)
            &gt; {{ $logLine }}<br>
        @endforeach
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Document generated automatically by PensAssistant analytics system based on provided user parameters.<br>
        PensAssistant CRM &copy; {{ date('Y') }} All rights reserved.
    </div>

</body>
</html>
