<?php

namespace App\Http\Requests\CompetencyGroup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetencyGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('competency_groups', 'name')->ignore($this->route('competencyGroup')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama rumpun wajib diisi.',
            'name.unique' => 'Rumpun dengan nama ini sudah ada.',
        ];
    }
}
