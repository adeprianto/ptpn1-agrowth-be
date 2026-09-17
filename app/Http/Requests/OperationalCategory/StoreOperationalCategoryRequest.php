<?php

namespace App\Http\Requests\OperationalCategory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationalCategoryRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:20', Rule::unique('operational_categories', 'code')],
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    
    public function messages()
    {
        return [
            'code.unique' => 'Kode Operasional sudah digunakan.'
        ];
    }
}
