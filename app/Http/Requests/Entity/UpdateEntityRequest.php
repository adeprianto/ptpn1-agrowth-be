<?php

namespace App\Http\Requests\Entity;

use App\Enums\EntityStatus;
use App\Enums\EntityType;
use App\Models\Entities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entityId = $this->route('entity')?->id;

        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('entities', 'id')],
            'type' => ['sometimes', 'required', Rule::enum(EntityType::class)],
            'level' => ['nullable', Rule::in(['1', '2', '3', '4'])],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('entities', 'code')->ignore($entityId)],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(EntityStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Entities $entity */
            $entity = $this->route('entity');

            $type = $this->filled('type')
                ? EntityType::from($this->input('type'))
                : EntityType::tryFrom($entity->type);

            $parentId = $this->has('parent_id') ? $this->input('parent_id') : $entity->parent_id;

            if (!$type) {
                return;
            }

            if (!empty($parentId) && (int) $parentId === $entity->id) {
                $validator->errors()->add('parent_id', 'Entity tidak boleh menjadi parent untuk dirinya sendiri.');
                return;
            }

            if (!empty($parentId) && $this->isDescendant($entity, (int) $parentId)) {
                $validator->errors()->add('parent_id', 'Parent tidak boleh berupa keturunan (child) dari entity ini.');
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

    // cek apakah $candidateParentId adalah salah satu keturunan (anak/cucu/dst) dari $entity
    private function isDescendant(Entities $entity, int $candidateParentId): bool
    {
        return in_array($candidateParentId, $this->collectDescendantIds($entity), true);
    }

    private function collectDescendantIds(Entities $entity): array
    {
        $ids = [];

        foreach ($entity->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->collectDescendantIds($child));
        }

        return $ids;
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Kode entity sudah digunakan.',
        ];
    }
}