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
            'activity_type' => $this->activity_type,
            'learning_sector' => $this->learning_sector,
            'learning_type' => $this->learning_type,
            'learning_hours' => $this->learning_hours,
            'cost' => $this->cost,
            'organizer' => $this->whenLoaded('organizer', fn () => $this->organizer ? [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
                'is_ptpn_group' => $this->organizer->is_ptpn_group,
            ] : null),
            'realizations_count' => $this->whenCounted('realizations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
