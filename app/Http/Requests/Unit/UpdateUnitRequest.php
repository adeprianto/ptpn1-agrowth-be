<?php

namespace App\Http\Requests\Unit;

use App\Enums\EntityStatus;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends StoreUnitRequest
{
    public function rules(): array
    {
        $unitId = $this->route('unit')?->id;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('entities', 'code')->ignore($unitId)],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'parent_id' => ['sometimes', 'required', 'integer', Rule::exists('entities', 'id')],
            'status' => ['nullable', Rule::enum(EntityStatus::class)],

            // dikirim lengkap: baris yang tidak ada di daftar ini akan dihapus
            'operationals' => ['nullable', 'array'],
            'operationals.*.operational_category_id' => ['required', 'integer', Rule::exists('operational_categories', 'id')],
            'operationals.*.business_type_id' => ['nullable', 'integer', Rule::exists('business_types', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        // status tidak dipaksa ACTIVE saat update
    }
}
