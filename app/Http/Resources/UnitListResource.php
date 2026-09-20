<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $operationalCategories = $this->entityOperationals
        ->pluck('operationalCategory')
        ->filter()
        ->unique('id')
        ->values();

        $businessTypes = $this->entityOperationals
        ->pluck('businessType')
        ->filter()
        ->unique('id')
        ->values();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            
            'jenis_unit' => $operationalCategories->map(fn ($c)
             => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
            ])->values(),

            'komoditas' => $businessTypes->map(fn ($b) => [
                'id' => $b->id,
                'code' => $b->code,
                'name' => $b->name,
            ])->values(),

            'regional' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ] : null),

            'total_karyawan' => 0,
            'kepala_unit' => null,
        ];
    }
}
