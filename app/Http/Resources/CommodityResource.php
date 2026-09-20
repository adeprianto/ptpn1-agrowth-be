<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommodityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->when($this->resource !== null, fn () => [
            'id' => $this->id,
            'entity_operational_id' => $this->entity_operational_id,
            'total_estate_area' => $this->total_estate_area,
            'planted_area' => $this->planted_area,
            'immature_area' => $this->immature_area,
            'next_planting_area' => $this->next_planting_area,
            'non_productive_area' => $this->non_productive_area,
            'other_area' => $this->other_area,
            'total_afdeling' => $this->total_afdeling,
            'total_factory' => $this->total_factory,
            'factory_capacity_kg' => $this->factory_capacity_kg,
            'processed_product' => $this->processed_product,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}