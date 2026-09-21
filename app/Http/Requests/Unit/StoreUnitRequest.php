<?php

namespace App\Http\Requests\Unit;

use App\Enums\EntityStatus;
use App\Models\Entities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUnitRequest extends FormRequest
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
            'parent_id' => ['required', 'integer', Rule::exists('entities', 'id')],
            'status' => ['nullable', Rule::enum(EntityStatus::class)],

            // satu unit bisa punya beberapa pasangan jenis + komoditas
            'operationals' => ['nullable', 'array'],
            'operationals.*.operational_category_id' => ['required', 'integer', Rule::exists('operational_categories', 'id')],
            'operationals.*.business_type_id' => ['nullable', 'integer', Rule::exists('business_types', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => EntityStatus::ACTIVE->value]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parent = Entities::find($this->input('parent_id'));

            if ($parent && $parent->type !== 'REGIONAL') {
                $validator->errors()->add('parent_id', 'Induk unit harus berupa Regional.');
            }

            $this->validateDuplicateOperationals($validator);
        });
    }

    // pasangan jenis + komoditas tidak boleh kembar dalam satu unit
    protected function validateDuplicateOperationals(Validator $validator): void
    {
        $seen = [];

        foreach ((array) $this->input('operationals', []) as $index => $row) {
            $key = ($row['operational_category_id'] ?? '').'-'.($row['business_type_id'] ?? '');

            if (in_array($key, $seen, true)) {
                $validator->errors()->add(
                    "operationals.{$index}",
                    'Pasangan jenis dan komoditas ini sudah ada di unit yang sama.'
                );
            }

            $seen[] = $key;
        }
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode unit wajib diisi.',
            'code.unique' => 'Kode unit sudah digunakan.',
            'name.required' => 'Nama unit wajib diisi.',
            'parent_id.required' => 'Regional induk wajib dipilih.',
            'operationals.*.operational_category_id.required' => 'Jenis unit wajib dipilih.',
        ];
    }
}
