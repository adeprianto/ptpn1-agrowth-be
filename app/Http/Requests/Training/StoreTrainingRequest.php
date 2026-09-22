<?php

namespace App\Http\Requests\Training;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'name' => ['required', 'string'],
            'hr_development_type' => ['required', 'string', 'max:255'],
            'competency_type' => ['required', 'string', 'max:255'],
            'learning_sector' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => true]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pelatihan wajib diisi.',
            'hr_development_type.required' => 'Jenis pengembangan SDM wajib dipilih.',
            'competency_type.required' => 'Jenis kompetensi wajib dipilih.',
            'learning_sector.required' => 'Bidang pembelajaran wajib dipilih.',
            'vendor_id.exists' => 'Penyelenggara pelatihan tidak ditemukan.',
        ];
    }
}
