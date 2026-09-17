<?php

namespace App\Http\Requests\PositionTitle;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionTitleRequest extends FormRequest
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
        $positionTitle = $this->route('positionTitle');
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('position_titles', 'code')->ignore($positionTitle->id)],
            'name' => ['required', 'string', 'max:150'],
            'level' => ['required', 'integer', 'min:1' , 'max:10'],
            'job_family_id' => ['nullable', 'exists:job_families,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
        ];
    }
}
