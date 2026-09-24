export interface UserItem {
    id: number | string;
    first_name: string;
    last_name: string;
    name: string;
    email: string;
    role: string;
    is_admin: boolean;
    is_suspended: boolean;
    is_trashed: boolean;
    created_at: string;
    deleted_at?: string | null;
}

export interface UserDetail extends UserItem {
    gender?: string | null;
    date_of_birth?: string | null;
    disability_group?: string | null;
    benefits?: string[];
    target_retirement_year?: number | null;
    calculations_count?: number;
    documents_count?: number;
    tax_histories_count?: number;
    updated_at?: string;
}

export interface DocumentItem {
    id: number;
    user_id: number;
    user_name: string;
    user_email: string;
    title: string;
    document_type: string;
    file_name: string;
    file_size: number;
    formatted_file_size: string;
    mime_type: string;
    status: string;
    created_at: string;
}

export interface DocumentDetail extends DocumentItem {
    user?: { name?: string; email?: string };
    recognized_data?: any;
}

export interface AdminCalculationItem {
    id: number;
    user_id: number;
    user_name: string;
    user_email: string;
    pension_type: string;
    target_retirement_year: number;
    total_service_months: number;
    total_service_years: number;
    kz_wage_coefficient: number;
    zp_macroeconomic_average: number;
    ks_service_coefficient: number;
    base_pension_amount: number;
    final_pension_amount: number;
    created_at: string;
}

export interface AdminCalculationDetail extends AdminCalculationItem {
    calculation_breakdown?: any;
    calculation_logs?: string[];
}

export interface TranslationItem {
    id?: number;
    group?: string;
    key: string;
    uk?: string;
    en?: string;
    text?: Record<string, string>;
    is_saving?: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface SystemErrorItem {
    id: number;
    user_id?: number | null;
    status_code: number;
    url: string;
    method: string;
    exception_class: string;
    message: string;
    stack_trace?: string | null;
    user_agent?: string | null;
    ip_address?: string | null;
    is_resolved: boolean;
    resolved_at?: string | null;
    resolved_by_id?: number | null;
    created_at?: string;
    user?: {
        id: number;
        first_name?: string;
        last_name?: string;
        email: string;
    } | null;
    resolver?: {
        id: number;
        first_name?: string;
        last_name?: string;
        email: string;
    } | null;
}
