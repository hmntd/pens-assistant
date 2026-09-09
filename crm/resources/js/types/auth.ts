export type User = {
    id: number | string;
    first_name: string;
    last_name: string;
    name?: string;
    is_admin?: boolean;
    email: string;
    avatar?: string;
    email_verified_at?: string | null;
    gender?: string | null;
    date_of_birth?: string | null;
    disability_group?: string | null;
    benefits?: string[] | null;
    target_retirement_year?: number | null;
    is_suspended?: boolean;
    provider_name?: string | null;
    provider_id?: string | null;
    two_factor_enabled?: boolean;
    created_at?: string | null;
    updated_at?: string | null;
    deleted_at?: string | null;
    role?: string;
    roles?: Array<string | { name: string }>;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
