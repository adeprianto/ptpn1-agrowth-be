<?php

namespace App\Http\Requests\TrainingRealization;

class UpdateDetailRequest extends StoreDetailRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'employee_id' => ['sometimes', 'required', 'exists:employees,id'],
            'experiental_learning_hours' => ['sometimes', 'required', 'integer', 'min:0'],
            'social_learning_hours' => ['sometimes', 'required', 'integer', 'min:0'],
            'formal_learning_hours' => ['sometimes', 'required', 'integer', 'min:0'],
            'duration_learning_hours' => ['sometimes', 'required', 'integer', 'min:0'],
            'learning_cost' => ['sometimes', 'required', 'integer', 'min:0'],
            'transport_cost' => ['sometimes', 'required', 'integer', 'min:0'],
            'perdiem_cost' => ['sometimes', 'required', 'integer', 'min:0'],
            'travel_expense_cost' => ['sometimes', 'required', 'integer', 'min:0'],
            'total_cost' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }
}
