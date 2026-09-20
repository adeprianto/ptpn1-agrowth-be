<?php

namespace App\Http\Requests\Organization;

use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('organization', 'code')],
            'nama' => ['required', 'string', 'max:150'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'organization_type_id' => ['required', 'exists:organization_types,id'],
            'entity_id' => ['required', 'exists:entities,id'],
            'job_function_id' => ['required', 'exists:job_functions,id'],
            'parent_id' => ['required', 'exists:organizations,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('parent_id')) {
                return;
            }

            $organization = $this->route('organization');
            $parentId = (int) $this->input('parent_id');

            if ($parentId === $organization->id) {
                $validator->errors()->add('parent_id', 'Organisasi tidak bisa menjadi induk untuk dirinya sendiri.');
                return;
            }

            $parent = Organization::find($parentId);

            if (! $parent) {
                return;
            }

            if ($this->filled('entity_id') && $parent->entity_id != $this->input('entity_id')) {
                $validator->errors()->add('parent_id', 'Parent organisasi harus berada pada entity yang sama.');
            }

            if ($this->isDescendant($organization, $parent)) {
                $validator->errors()->add('parent_id', 'Parent yang dipilih adalah turunan dari organisasi ini (circular reference).');
            }
        }); 
    }

    private function isDescendant(Organization $organization, Organization $candidateParent): bool
    {
        $current = $candidateParent;

        while ($current->parent_id !== null) {
            if ($current->parent_id === $organization->id) {
                return true;
            }

            $current = $current->parent;

            if (! $current) {
                break;
            }
        }

        return false;
    }
    
}
