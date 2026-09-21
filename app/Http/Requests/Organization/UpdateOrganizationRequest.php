<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends StoreOrganizationRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->route('organization')?->id;

        return [
            ...parent::rules(),
            'code' => [
                'sometimes', 'required', 'string', 'max:30',
                Rule::unique('organizations', 'code')->ignore($organizationId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'level' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'organization_type_id' => ['sometimes', 'required', 'exists:organization_types,id'],
            'entity_id' => ['sometimes', 'required', 'exists:entities,id'],
        ];
    }
}
