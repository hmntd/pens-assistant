<!DOCTYPE html>
<html lang="uk">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Пенсійний розрахунок #{{ $calculation->id }} | PensAssistant</title>
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
                <div class="brand-subtitle">Звіт розрахунку пенсії</div>
            </td>
            <td class="doc-meta">
                <strong>ID розрахунку:</strong> #{{ $calculation->id }}<br>
                <strong>Дата формування:</strong> {{ now()->format('d.m.Y H:i') }}<br>
                <strong>Версія ядра C++:</strong> 2.4.0
            </td>
        </tr>
    </table>

    <!-- User Information -->
    <div class="section-title">1. Дані користувача та параметри розрахунку</div>
    <table class="info-grid">
        <tr>
            <td width="50%">
                <div class="info-label">ПІБ Користувача:</div>
                <div class="info-val">{{ $user->name }}</div>
            </td>
            <td width="50%">
                <div class="info-label">Email адреса:</div>
                <div class="info-val">{{ $user->email }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="info-label">Стать:</div>
                <div class="info-val">{{ $user->gender === 'FEMALE' ? 'Жіноча' : ($user->gender === 'MALE' ? 'Чоловіча' : 'Не вказано') }}</div>
            </td>
            <td>
                <div class="info-label">Дата народження:</div>
                <div class="info-val">{{ $user->date_of_birth ?? 'Не вказано' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="info-label">Група інвалідності:</div>
                <div class="info-val">{{ $user->disability_group ?? 'Відсутня' }}</div>
            </td>
            <td>
                <div class="info-label">Цільовий рік виходу на пенсію:</div>
                <div class="info-val">{{ $calculation->input_parameters['target_retirement_year'] ?? $calculation->target_retirement_year ?? date('Y') }} р.</div>
            </td>
        </tr>
    </table>

    <!-- Summary Box -->
    <div class="summary-card">
        <table>
            <tr>
                <td>
                    <div style="font-size: 11px; color: #065f46; font-weight: bold;">ПІДСУМКОВИЙ РОЗМІР ПЕНСІЇ</div>
                    <div class="hero-price">{{ number_format((float)($calculation->final_pension ?? $calculation->final_pension_amount ?? 0), 2, '.', ' ') }} ₴ / грн</div>
                </td>
                <td style="text-align: right; font-size: 10px; color: #047857;">
                    Базова пенсія: <strong>{{ number_format((float)($calculation->base_pension ?? $calculation->base_pension_amount ?? 0), 2, '.', ' ') }} ₴</strong><br>
                    Страховий стаж: <strong>{{ floor(($calculation->total_service_months ?? 0) / 12) }} р. {{ ($calculation->total_service_months ?? 0) % 12 }} міс.</strong> ({{ $calculation->total_service_months ?? 0 }} міс.)
                </td>
            </tr>
        </table>
    </div>

    <!-- Coefficients & Formula Breakdown -->
    <div class="section-title">2. Коефіцієнти та формула обчислення</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Показник / Коефіцієнт</th>
                <th>Розраховане значення</th>
                <th>Опис та формула (Закон № 1058-IV)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Kz (Коефіцієнт заробітку)</strong></td>
                <td><strong>{{ number_format((float)($calculation->kz_wage_coefficient ?? 1.0), 4) }}</strong></td>
                <td>Середнє співвідношення місячної заробітної плати до середньої зарплати в Україні</td>
            </tr>
            <tr>
                <td><strong>Zp (Середня зарплата в Україні)</strong></td>
                <td><strong>{{ number_format((float)($calculation->zp_macroeconomic_average ?? 16500), 2, '.', ' ') }} ₴</strong></td>
                <td>Показник середньої заробітної плати за 3 роки, що передують року звернення</td>
            </tr>
            <tr>
                <td><strong>Ks (Коефіцієнт страхового стажу)</strong></td>
                <td><strong>{{ number_format((float)($calculation->ks_service_coefficient ?? 0.35), 4) }}</strong></td>
                <td>Формула: (Місяці стажу * 1%) / 1200. Ваговий коефіцієнт оцінки стажу</td>
            </tr>
            <tr>
                <td><strong>Пенсія за віком (Базова)</strong></td>
                <td><strong>{{ number_format((float)($calculation->base_pension ?? $calculation->base_pension_amount ?? 0), 2, '.', ' ') }} ₴</strong></td>
                <td>Формула: P_base = Zp * Kz * Ks</td>
            </tr>
        </tbody>
    </table>

    <!-- C++ Execution Audit Logs (if present) -->
    @if(!empty($calculation_logs) && count($calculation_logs) > 0)
    <div class="section-title">3. Протокол виконання обчислювального движка C++</div>
    <div class="logs-box">
        @foreach($calculation_logs as $logLine)
            &gt; {{ $logLine }}<br>
        @endforeach
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Документ згенеровано аналітичною системою PensAssistant на основі наданих даних.<br>
        PensAssistant CRM &copy; {{ date('Y') }} All rights reserved.
    </div>

</body>
</html>
