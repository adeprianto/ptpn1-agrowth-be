<?php

namespace App\Models;

use App\Enums\CompetencyItemType;
use App\Enums\CompetencyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competencies extends Model
{
    protected $table = 'competencies';

    protected $fillable = [
        'competency_group_id',
        'code',
        'name',
        'name_en',
        'definition',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CompetencyGroups::class, 'competency_group_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(CompetencyLevels::class, 'competency_id')->orderBy('level');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompetencyItems::class, 'competency_id')->orderBy('sort_order');
    }

    public function skills(): HasMany
    {
        return $this->items()->where('type', CompetencyItemType::Skill);
    }

    public function knowledge(): HasMany
    {
        return $this->items()->where('type', CompetencyItemType::Knowledge);
    }

    public function specialization(): HasMany
    {
        return $this->items()->where('type', CompetencyItemType::Specialization);
    }

    public function positionTitles(): BelongsToMany
    {
        return $this->belongsToMany(PositionTitle::class, 'position_title_competencies', 'competency_id', 'position_title_id')
            ->using(PositionTitleCompetencies::class)
            ->withPivot('required_level')
            ->withTimestamps();
    }

    /**
     * Ganti seluruh skill, knowledge, dan spesialisasi dengan daftar baru.
     * Urutan daftar disimpan sebagai `sort_order`.
     *
     * @param  array<string, array<int, string>>  $lists  nilai CompetencyItemType => daftar teks
     */
    public function syncItems(array $lists): void
    {
        $this->items()->delete();

        foreach (CompetencyItemType::cases() as $type) {
            foreach (self::cleanList($lists[$type->value] ?? []) as $order => $name) {
                $this->items()->create(['type' => $type, 'name' => $name, 'sort_order' => $order]);
            }
        }

        $this->unsetRelation('items');
    }

    /**
     * Simpan uraian level 1-6; indeks 0 = level 1.
     *
     * @param  array<int, string|null>  $descriptions
     */
    public function syncLevels(array $descriptions): void
    {
        foreach (CompetencyLevel::cases() as $level) {
            $this->levels()->updateOrCreate(
                ['level' => $level->value],
                ['description' => trim((string) ($descriptions[$level->value - 1] ?? '')) ?: null],
            );
        }

        $this->unsetRelation('levels');
    }

    /**
     * Rapikan daftar teks: buang spasi berlebih, isian kosong, dan duplikat
     * (tanpa memandang besar-kecil huruf), urutan pertama dipertahankan.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, string>
     */
    public static function cleanList(array $items): array
    {
        $result = [];
        $seen = [];

        foreach ($items as $item) {
            $text = trim(preg_replace('/\s+/u', ' ', (string) $item));
            $key = mb_strtolower($text);

            if ($text !== '' && ! isset($seen[$key])) {
                $result[] = $text;
                $seen[$key] = true;
            }
        }

        return $result;
    }
}
