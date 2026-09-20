<?php

namespace App\Http\Requests\TrainingRealization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingRealizationRequest extends FormRequest
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
            'training_name' => ['sometimes','required', 'string', 'max:200'],
            'training_id' => ['nullable', 'exists:trainings,id'],
            'training_start_date' => ['sometimes','required', 'date'],
            'training_end_date' => ['sometimes','required', 'date', 'after_or_equal:training_start_date'],
        ];
    }
}
