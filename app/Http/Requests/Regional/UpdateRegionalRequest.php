<?php

namespace App\Http\Requests\Regional;

use App\Enums\EntityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regionalId = $this->route('regional')?->id;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('entities', 'code')->ignore($regionalId)],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(EntityStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Kode regional sudah digunakan.',
        ];
    }
}
