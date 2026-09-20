<?php

namespace App\Http\Requests\EntityOperational;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEntityOperationalRequest extends FormRequest
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
        $id = $this->route('entityOperational')?->id;

        return [
            'entity_id' => ['sometimes', 'required', 'integer', Rule::exists('entities', 'id')],
            'operational_category_id' => ['sometimes', 'required', 'integer', Rule::exists('operational_category', 'id')],
            'business_type_id' => ['nullable', 'integer', Rule::exists('business_type', 'id')],
            'code' => ['required', 'string', 'max:50', Rule::unique('entity_operationals', 'code')]
        ];
    }
}
