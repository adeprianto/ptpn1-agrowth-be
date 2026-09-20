<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'name' => $this->name,
            'organization_type' => $this->whenLoaded('organizationType', fn () => [
                'id' => $this->organizationType->id,
                'code' => $this->organizationType->code,
                'name' => $this->organizationType->name,
            ]),
            'entity' => $this->whenLoaded('entity', fn () => [
                'id' => $this->entity->id,
                'code' => $this->entity->code,
                'name' => $this->entity->name,
            ]),
            'job_function' => $this->whenLoaded('jobFunction', fn () => $this->jobFunction ? [
                'id' => $this->jobFunction->id,
                'code' => $this->jobFunction->code,
                'name' => $this->jobFunction->name,
            ] : null),
            'parent' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ] : null),
            'children_count' => $this->whenCounted('children'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
