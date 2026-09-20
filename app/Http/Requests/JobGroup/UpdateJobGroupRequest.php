<?php

namespace App\Http\Requests\JobGroup;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobGroupRequest extends FormRequest
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
        $jobGroup = $this->route('jobGroup');

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('job_groups', 'code')->ignore($jobGroup->id)],
            'name' => ['required', 'string', 'max:100']
        ];
    }
}
