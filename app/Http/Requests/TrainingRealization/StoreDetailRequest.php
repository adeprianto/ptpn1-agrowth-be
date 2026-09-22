<?php

namespace App\Http\Requests\TrainingRealization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kolom periode (year, month, tanggal, durasi) boleh dikosongkan — kalau
     * tidak dikirim, controller mengisinya dari realisasi induknya.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration_days' => ['nullable', 'integer', 'min:0'],
            'learning_hours_per_day' => ['nullable', 'integer', 'min:0'],
            'experiental_learning_hours' => ['required', 'integer', 'min:0'],
            'social_learning_hours' => ['required', 'integer', 'min:0'],
            'formal_learning_hours' => ['required', 'integer', 'min:0'],
            'duration_learning_hours' => ['required', 'integer', 'min:0'],
            'learning_cost' => ['required', 'integer', 'min:0'],
            'transport_cost' => ['required', 'integer', 'min:0'],
            'perdiem_cost' => ['required', 'integer', 'min:0'],
            'travel_expense_cost' => ['required', 'integer', 'min:0'],
            'total_cost' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Pegawai peserta wajib dipilih.',
            'employee_id.exists' => 'Pegawai tidak ditemukan.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
