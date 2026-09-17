<?php

namespace App\Http\Requests\PositionTitle;

use App\Enums\BodLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePositionTitleRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:30', Rule::unique('position_titles', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'level' => ['nullable', new Enum(BodLevel::class)],
            'job_family_id' => ['nullable', 'exists:job_families,id'],
            'organization_id' => ['nullable', 'exists:organizaion,id'],
        ];
    }
}
