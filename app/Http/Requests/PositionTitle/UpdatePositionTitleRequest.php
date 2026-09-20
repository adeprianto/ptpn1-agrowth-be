<?php

namespace App\Http\Requests\PositionTitle;

use App\Enums\BodLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
            'name' => ['required', 'string', 'max:200'],
            'name_sap' => ['nullable', 'string', 'max:200'],
            'level_bod' => ['nullable', new Enum(BodLevel::class)],
            'job_group_id' => ['nullable', 'exists:job_groups,id'],
            'job_function_id' => ['nullable', 'exists:job_functions,id'],
        ];
    }
}
