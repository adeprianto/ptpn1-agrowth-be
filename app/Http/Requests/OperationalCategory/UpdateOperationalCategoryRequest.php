<?php

namespace App\Http\Requests\OperationalCategory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationalCategoryRequest extends FormRequest
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
        $id = $this->route('operationalCategory')?->id;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('operational_categories', 'code')->ignore($id)],
            'name' => ['sometimes', 'required', 'string', 'max:100']
        ];
    }
}
