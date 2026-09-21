<?php

namespace App\Http\Requests\Regional;

use App\Enums\EntityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('entities', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(EntityStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => EntityStatus::ACTIVE->value]);
        }
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode regional wajib diisi.',
            'code.unique' => 'Kode regional sudah digunakan.',
            'name.required' => 'Nama regional wajib diisi.',
        ];
    }
}
