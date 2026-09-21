<?php

namespace App\Http\Requests\Organization;

use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrganizationRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:30', Rule::unique('organizations', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'organization_type_id' => ['required', 'exists:organization_types,id'],
            'entity_id' => ['required', 'exists:entities,id'],
            // kolom Function Code kosong 100% di data import, jadi tidak diwajibkan
            'job_function_id' => ['nullable', 'exists:job_functions,id'],
            // kosong = departemen paling atas (root) di entity tersebut
            'parent_id' => ['nullable', 'exists:organizations,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('parent_id')) {
                return;
            }

            /** @var Organization|null $organization null saat create */
            $organization = $this->route('organization');
            $parentId = (int) $this->input('parent_id');

            if ($organization && $parentId === $organization->id) {
                $validator->errors()->add('parent_id', 'Departemen tidak bisa menjadi induk untuk dirinya sendiri.');

                return;
            }

            $parent = Organization::find($parentId);

            if (! $parent) {
                return;
            }

            $entityId = $this->input('entity_id', $organization?->entity_id);

            if ($entityId && (int) $parent->entity_id !== (int) $entityId) {
                $validator->errors()->add('parent_id', 'Induk departemen harus berada pada entity yang sama.');
            }

            if ($organization && $this->isDescendant($organization, $parent)) {
                $validator->errors()->add('parent_id', 'Induk yang dipilih adalah turunan dari departemen ini (circular reference).');
            }
        });
    }

    private function isDescendant(Organization $organization, Organization $candidateParent): bool
    {
        $current = $candidateParent;

        while ($current->parent_id !== null) {
            if ((int) $current->parent_id === (int) $organization->id) {
                return true;
            }

            $current = $current->parent;

            if (! $current) {
                break;
            }
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode departemen wajib diisi.',
            'code.unique' => 'Kode departemen sudah digunakan.',
            'name.required' => 'Nama departemen wajib diisi.',
            'organization_type_id.required' => 'Tipe departemen wajib dipilih.',
            'entity_id.required' => 'Entity wajib dipilih.',
        ];
    }
}
