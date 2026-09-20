<?php

namespace App\Http\Requests\Commodity;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCommodityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ESTATE
            'total_estate_area' => ['nullable', 'numeric', 'min:0'],
            'planted_area' => ['nullable', 'numeric', 'min:0'],
            'immature_area' => ['nullable', 'numeric', 'min:0'],
            'next_planting_area' => ['nullable', 'numeric', 'min:0'],
            'non_productive_area' => ['nullable', 'numeric', 'min:0'],
            'other_area' => ['nullable', 'numeric', 'min:0'],
            'total_afdeling' => ['nullable', 'integer', 'min:0'],

            // FACTORY
            'total_factory' => ['nullable', 'integer', 'min:0'],
            'factory_capacity_kg' => ['nullable', 'numeric', 'min:0'],
            'processed_product' => ['nullable', 'string', 'max:100'],
        ];
    }
}