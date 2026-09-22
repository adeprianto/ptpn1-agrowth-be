<?php

namespace App\Http\Requests\Vendor;

use App\Enums\VendorType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'classification' => ['required', Rule::enum(VendorType::class)],
            'is_lpp' => ['nullable', 'boolean'],

            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],

            'pic_name' => ['nullable', 'string', 'max:150'],
            'pic_phone' => ['nullable', 'string', 'max:30'],
            'pic_email' => ['nullable', 'email', 'max:150'],
            'pic_position' => ['nullable', 'string', 'max:100'],

            'status' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => true]);
        }
    }

    /** is_lpp diturunkan dari type, tidak dikirim frontend */
    public function validatedWithFlags(): array
    {
        $data = $this->validated();
        $data['is_lpp'] = VendorType::from($data['classification'])->isLpp();

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama penyelenggara wajib diisi.',
            'classification.required' => 'Jenis penyelenggara wajib dipilih.',
            'email.email' => 'Format email tidak valid.',
            'pic_email.email' => 'Format email PIC tidak valid.',
        ];
    }
}
