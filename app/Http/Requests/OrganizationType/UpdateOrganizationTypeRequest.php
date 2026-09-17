<?php

namespace App\Http\Requests\OrganizationType;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationTypeRequest extends FormRequest
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
        $organizationType = $this->route('organizationType');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('organization_types', 'code')->ignore($organizationType->id)],
            'name' => ['required', 'string', 'max:100']
        ];
    }
}
