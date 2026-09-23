<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hr_development_type' => $this->hr_development_type,
            'competency_type' => $this->competency_type,
            'learning_sector' => $this->learning_sector,
            'description' => $this->description,
            'status' => $this->status,
            'vendor_id' => $this->vendor_id,
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'classification' => $this->vendor->classification?->value,
                'is_lpp' => $this->vendor->is_lpp,
            ] : null),
            // Tag dikirim sebagai daftar teks: nilainya tidak punya tabel master,
            // jadi id barisnya tidak berguna di sisi frontend.
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->all()),
            'realizations_count' => $this->whenCounted('realizations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
