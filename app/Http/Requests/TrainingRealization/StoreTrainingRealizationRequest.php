<?php

namespace App\Http\Requests\TrainingRealization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRealizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kolom total_* sengaja tidak divalidasi di sini: nilainya selalu dihitung
     * ulang dari detail peserta lewat TrainingRealizations::recalculateTotals().
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'training_id' => ['nullable', 'exists:trainings,id'],
            'learning_method' => ['required', 'string', 'max:255'],
            'learning_location' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'duration_days' => ['required', 'integer', 'min:0'],
            'learning_hours_per_day' => ['required', 'integer', 'min:0'],
            'financing_category' => ['required', 'string', 'max:255'],
            'cost_allocation' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'training_id.exists' => 'Pelatihan tidak ditemukan.',
            'learning_method.required' => 'Metode pembelajaran wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'financing_category.required' => 'Kategori pembiayaan wajib dipilih.',
            'cost_allocation.required' => 'Alokasi pembiayaan wajib dipilih.',
        ];
    }
}
