<?php

namespace App\Http\Requests\Entity;

use App\Enums\EntityStatus;
use App\Enums\EntityType;
use App\Models\Entities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('entities', 'id')],
            'type' => ['required', Rule::enum(EntityType::class)],
            'level' => ['nullable', Rule::in(['1', '2', '3', '4'])],
            'code' => ['required', 'string', 'max:50', Rule::unique('entities', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(EntityStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        // auto-isi level dari type kalau tidak dikirim
        if ($this->filled('type') && !$this->filled('level')) {
            $type = EntityType::tryFrom($this->input('type'));
            if ($type) {
                $this->merge(['level' => $type->defaulLevel()]);
            }
        }

        if (!$this->filled('status')) {
            $this->merge(['status' => EntityStatus::ACTIVE->value]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type') ? EntityType::tryFrom($this->input('type')) : null;
            $parentId = $this->input('parent_id');

            if (!$type) {
                return;
            }

            $expectedParentType = $type->allowedParentType();

            if ($expectedParentType === null && !empty($parentId)) {
                $validator->errors()->add('parent_id', 'Entity dengan tipe HEAD_OFFICE tidak boleh memiliki parent.');
                return;
            }

            if ($expectedParentType !== null && empty($parentId)) {
                $validator->errors()->add('parent_id', "Entity dengan tipe {$type->value} wajib memiliki parent.");
                return;
            }

            if ($expectedParentType !== null && !empty($parentId)) {
                $parent = Entities::find($parentId);

                if ($parent && $parent->type !== $expectedParentType->value) {
                    $validator->errors()->add(
                        'parent_id',
                        "Parent untuk tipe {$type->value} harus bertipe {$expectedParentType->value}, bukan {$parent->type}."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Tipe entity wajib diisi.',
            'code.required' => 'Kode entity wajib diisi.',
            'code.unique' => 'Kode entity sudah digunakan.',
            'name.required' => 'Nama entity wajib diisi.',
        ];
    }
}