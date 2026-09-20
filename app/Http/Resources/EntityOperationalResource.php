<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityOperationalResource extends JsonResource
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
            'code' => $this->code,
            'entity_id' => $this->entity_id,
            'operational_category_id' => $this->operational_category_id,
            'business_type_id' => $this->business_type_id,
            'entity' => $this->whenLoaded('entity', fn() => [
                'id' => $this->id,
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
            ]),
            'operational_category' => new OperationalCategoryResource($this->whenLoaded('operationalCategory')),
            'business_type' => new BusinessTypeResource($this->whenLoaded('businessType')),
            'commodity' => new CommodityResource($this->whenLoaded('commodity')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
