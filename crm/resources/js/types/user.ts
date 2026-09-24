export interface UserDetailsForm {
    first_name: string;
    last_name: string;
    email: string;
    gender: string | null;
    date_of_birth: string | null;
    disability_group: string;
    pension_type: string;
    target_retirement_year: number | string | null;
    benefits: string[];
}
