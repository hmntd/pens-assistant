<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePensionCalculationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gender' => 'nullable|string|in:male,female,MALE,FEMALE',
            'date_of_birth' => 'nullable|date',
            'retirement_date' => 'nullable|date',
            'pension_type' => 'nullable|string|in:old_age,disability,loss_of_breadwinner,OLD_AGE,DISABILITY,LOSS_OF_BREADWINNER',
            'disability_group' => 'nullable|string|in:none,group_1,group_2,group_3,DISABILITY_NONE,GROUP_1,GROUP_2,GROUP_3',
            'dependents_count' => 'nullable|integer|min:0',
            'employment_history' => 'nullable|array',
            'employment_history.*.start_date' => 'required_with:employment_history|date',
            'employment_history.*.end_date' => 'required_with:employment_history|date|after_or_equal:employment_history.*.start_date',
            'employment_history.*.multiplier' => 'nullable|numeric|min:0.1|max:5.0',
            'salary_history' => 'nullable|array',
            'salary_history.*.year' => 'required_with:salary_history|integer|min:1950|max:2099',
            'salary_history.*.month' => 'required_with:salary_history|integer|between:1,12',
            'salary_history.*.amount' => 'required_with:salary_history|numeric|min:0',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string|in:combat_veteran,honorary_donor,chornobyl_liquidator,disabled_child_care,age_supplement,COMBAT_VETERAN,HONORARY_DONOR,CHORNOBYL_LIQUIDATOR,DISABLED_CHILD_CARE,AGE_SUPPLEMENT',
            'enable_optimization_rule' => 'nullable|boolean',
            'enable_hypothetical_projection' => 'nullable|boolean',
            'is_hypothetical_projection' => 'nullable|boolean',
            'zp_macroeconomic_average' => 'nullable|numeric|min:0',
            'target_user_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
