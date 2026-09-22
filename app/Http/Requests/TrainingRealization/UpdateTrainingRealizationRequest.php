<?php

namespace App\Http\Requests\TrainingRealization;

class UpdateTrainingRealizationRequest extends StoreTrainingRealizationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'learning_method' => ['sometimes', 'required', 'string', 'max:255'],
            'year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['sometimes', 'required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'duration_days' => ['sometimes', 'required', 'integer', 'min:0'],
            'learning_hours_per_day' => ['sometimes', 'required', 'integer', 'min:0'],
            'financing_category' => ['sometimes', 'required', 'string', 'max:255'],
            'cost_allocation' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
