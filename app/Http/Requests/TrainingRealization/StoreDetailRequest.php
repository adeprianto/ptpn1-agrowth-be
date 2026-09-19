<?php

namespace App\Http\Requests\TrainingRealization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDetailRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'employee_name' => ['required_without:employee_id', 'nullable', 'string', 'max:150'],

            'training_id' => ['nullable', 'exists:trainings,id'],
            'training_start_date' => ['nullable', 'date'],
            'training_end_date' => ['nullable', 'date', 'after_or_equal:training_start_date'],
            'learning_hours' => ['nullable', 'integer', 'min:0'],
            'cost' => ['nullable', 'integer', 'min:0'],

            'position_title_id' => ['nullable', 'exists:position_titles,id'],
            'entity_id' => ['nullable', 'exists:entities,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],

            'employee_position' => ['nullable', 'string', 'max:200'],
            'employee_bod_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'employee_unit' => ['nullable', 'string', 'max:150'],
            'employee_division' => ['nullable', 'string', 'max:150'],
            'employee_region' => ['nullable', 'string', 'max:150'],
        ];
    }
}
