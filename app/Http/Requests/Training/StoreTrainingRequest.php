<?php

namespace App\Http\Requests\Training;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'activity_type' => ['nullable', 'string', 'max:50'],
            'learning_sector' => ['nullable', 'string', 'max:50'],
            'learning_type' => ['nullable', 'string', 'max:50'],
            'learning_hours' => ['required', 'integer', 'min:0'],
            'cost' => ['required', 'integer', 'min:0'],
            'organizer_id' => ['nullable', 'exists:organizers,id'],
        ];
    }
}
