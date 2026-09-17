<?php

namespace App\Http\Requests\BusinessType;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessTypeRequest extends FormRequest
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
        $id = $this->route('businessType')?->id;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('business_types', 'code')->ignore($id)],
            'name' => ['sometimes', 'required', 'string', 'max:100']
        ];
    }
}
