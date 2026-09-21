<?php

namespace App\Http\Requests\Organizer;

use App\Enums\OrganizerType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizerRequest extends FormRequest
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
            'type' => ['required', Rule::enum(OrganizerType::class)],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],

            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],

            'pic_name' => ['nullable', 'string', 'max:150'],
            'pic_phone' => ['nullable', 'string', 'max:30'],
            'pic_email' => ['nullable', 'email', 'max:150'],
            'pic_position' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => 'ACTIVE']);
        }
    }

    /** is_ptpn_group diturunkan dari type, tidak dikirim frontend */
    public function validatedWithFlags(): array
    {
        $data = $this->validated();
        $data['is_ptpn_group'] = OrganizerType::from($data['type'])->isPtpnGroup();

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama penyelenggara wajib diisi.',
            'type.required' => 'Jenis penyelenggara wajib dipilih.',
            'email.email' => 'Format email tidak valid.',
            'pic_email.email' => 'Format email PIC tidak valid.',
        ];
    }
}
