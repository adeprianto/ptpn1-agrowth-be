<?php

namespace App\Http\Requests\JobFunction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobFunctionRequest extends FormRequest
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
        $jobFunction = $this->route('JobFunction');

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('job_functions', 'code')->ignore($jobFunction->id)],
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
