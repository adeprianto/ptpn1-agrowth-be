<?php

namespace App\Http\Resources;

use App\Enums\CompetencyItemType;
use App\Enums\CompetencyLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetencyResource extends JsonResource
{
    /**
     * Daftar butir dan uraian level hanya ikut kalau relasinya dimuat
     * (halaman detail); di daftar cukup jumlah skill & knowledge-nya.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $names = fn (CompetencyItemType $type) => $this->items
            ->filter(fn ($item) => $item->type === $type)
            ->pluck('name')
            ->values();

        return [
            'id' => $this->id,
            'competency_group_id' => $this->competency_group_id,
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group->id,
                'name' => $this->group->name,
            ]),
            'code' => $this->code,
            // nama Indonesia; name_en = nama asli "Technical Competency"
            'name' => $this->name,
            'name_en' => $this->name_en,
            'definition' => $this->definition,
            'skills_count' => $this->whenCounted('skills'),
            'knowledge_count' => $this->whenCounted('knowledge'),
            'skills' => $this->whenLoaded('items', fn () => $names(CompetencyItemType::Skill)),
            'knowledge' => $this->whenLoaded('items', fn () => $names(CompetencyItemType::Knowledge)),
            'specializations' => $this->whenLoaded('items', fn () => $names(CompetencyItemType::Specialization)),
            // selalu 6 butir berurutan level 1 s/d 6, walau ada yang belum diisi
            'levels' => $this->whenLoaded('levels', fn () => array_map(fn (CompetencyLevel $level) => [
                'level' => $level->value,
                'label' => $level->label(),
                'description' => $this->levels->first(fn ($row) => $row->level === $level)?->description,
            ], CompetencyLevel::cases())),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
