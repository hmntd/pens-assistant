export interface CalculationBreakdown {
    is_hypothetical?: boolean;
    criteria_met?: boolean;
    hypothetical_disclaimer?: string;
    logs?: string[];
    pre_clamped?: number;
    is_min_clamped?: boolean;
    is_max_clamped?: boolean;
}

export interface AppliedBenefit {
    benefit: string;
    name: string;
    amount: number;
}

export interface CalculationItem {
    id: number;
    status?: 'pending' | 'completed' | 'failed';
    error_message?: string | null;
    final_pension: number;
    base_pension: number;
    zp_macroeconomic_average?: number;
    kz_wage_coefficient?: number;
    ks_service_coefficient?: number;
    total_service_months?: number;
    recalculate_delta?: number;
    coefficient_multiplier?: number;
    disability_group?: string | null;
    created_at?: string;
    calculation_breakdown?: CalculationBreakdown;
    calculation_logs?: string[];
    applied_benefits?: AppliedBenefit[];
    input_parameters?: Record<string, any>;
}

export interface PensionForm {
    target_retirement_year: number | null;
    disability_group: string;
    service_years: number;
    enable_hypothetical_projection: boolean;
}
