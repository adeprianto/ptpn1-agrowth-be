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
        // satu unit bisa punya beberapa baris operasional (mis. Kebun Teh + Kebun Kopi + Pabrik Kopi),
        // jadi jenis & komoditas diratakan tanpa duplikat
        $pluckUnique = fn (string $relation) => $this->operationals
            ->pluck($relation)
            ->filter()
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
            ])
            ->values();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'jenis' => $pluckUnique('operationalCategory'),
            'komoditas' => $pluckUnique('businessType'),

            // pasangan asli per baris entity_operationals (dipakai form edit unit)
            'operasional' => $this->operationals->map(fn ($eo) => [
                'id' => $eo->id,
                'code' => $eo->code,
                'operational_category_id' => $eo->operational_category_id,
                'business_type_id' => $eo->business_type_id,
            ])->values(),
            'regional' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ] : null),
            'jumlah_karyawan' => (int) $this->jumlah_karyawan,
        ];
    }
}
