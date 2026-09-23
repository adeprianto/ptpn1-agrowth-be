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
            'training_id' => $this->training_id,
            'training' => $this->whenLoaded('training', fn () => $this->training ? [
                'id' => $this->training->id,
                'name' => $this->training->name,
                'hr_development_type' => $this->training->hr_development_type,
                'competency_type' => $this->training->competency_type,
                'learning_sector' => $this->training->learning_sector,
                'vendor' => $this->training->relationLoaded('vendor') && $this->training->vendor ? [
                    'id' => $this->training->vendor->id,
                    'name' => $this->training->vendor->name,
                ] : null,
            ] : null),
            'learning_method' => $this->learning_method,
            'learning_city' => $this->learning_city,
            'learning_location' => $this->learning_location,
            'year' => $this->year,
            'month' => $this->month,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration_days' => $this->duration_days,
            'learning_hours_per_day' => $this->learning_hours_per_day,
            'financing_category' => $this->financing_category,
            'cost_allocation' => $this->cost_allocation,
            // seluruh angka di bawah ini dihitung ulang dari detail peserta
            'total_participants' => $this->total_participants,
            'total_experiental_learning_hours' => $this->total_experiental_learning_hours,
            'total_social_learning_hours' => $this->total_social_learning_hours,
            'total_formal_learning_hours' => $this->total_formal_learning_hours,
            'total_duration_learning_hours' => $this->total_duration_learning_hours,
            'total_learning_cost' => $this->total_learning_cost,
            'total_transport_cost' => $this->total_transport_cost,
            'total_perdiem_cost' => $this->total_perdiem_cost,
            'total_travel_expense_cost' => $this->total_travel_expense_cost,
            'total_cost' => $this->total_cost,
            'details_count' => $this->whenCounted('details'),
            'details' => TrainingRealizationDetailResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
