<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingRealizationResource extends JsonResource
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
            'training_name' => $this->training_name,
            'training' => $this->whenLoaded('training', fn () => $this->training ? [
                'id' => $this->training->id,
                'name' => $this->training->name,
                'activity_type' => $this->training->activity_type,
            ] : null),
            'training_start_date' => $this->training_start_date?->toDateString(),
            'training_end_date' => $this->training_end_date?->toDateString(),
            'total_participants' => $this->total_participants,
            'total_learning_hours' => $this->total_learning_hours,
            'cost' => $this->cost,
            'details' => TrainingRealizationDetailResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
