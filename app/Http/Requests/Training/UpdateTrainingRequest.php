<?php

namespace App\Http\Requests\Training;

class UpdateTrainingRequest extends StoreTrainingRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'vendor_id' => ['sometimes', 'required', 'exists:vendors,id'],
            'name' => ['sometimes', 'required', 'string'],
            'hr_development_type' => ['sometimes', 'required', 'string', 'max:255'],
            'competency_type' => ['sometimes', 'required', 'string', 'max:255'],
            'learning_sector' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // status tidak dipaksa aktif saat update
    }
}
