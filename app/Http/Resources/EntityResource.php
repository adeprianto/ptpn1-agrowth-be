<?php

namespace App\Http\Resources;

use App\Enums\EntityType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'level' => $this->level,
            'type' => $this->type,
            'type_label' => EntityType::tryFrom($this->type)?->label(),
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
                'type' => $this->parent->type,
            ]),
            'children_count' => $this->whenCounted('children'),
            'children' => EntityResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}