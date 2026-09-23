<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trainings extends Model
{
    use HasFactory;

    protected $table = 'trainings';

    protected $fillable = [
        'vendor_id',
        'name',
        'hr_development_type',
        'competency_type',
        'learning_sector',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function realizations(): HasMany
    {
        return $this->hasMany(TrainingRealizations::class, 'training_id');
    }

    /** Tag pelatihan, satu baris per tag. */
    public function tags(): HasMany
    {
        return $this->hasMany(TrainingTags::class, 'training_id');
    }

    /**
     * Samakan daftar tag pelatihan ini dengan `$names`.
     *
     * Tag yang sudah ada dibiarkan (id-nya tidak berubah), yang hilang dari
     * daftar dihapus, yang baru ditambahkan. Mengirim array kosong berarti
     * menghapus semua tag.
     *
     * @param  array<int, string>  $names
     */
    public function syncTags(array $names): void
    {
        $wanted = self::normalizeTagNames($names);
        $current = $this->tags()->pluck('name')->all();

        $removed = array_diff($current, $wanted);
        if ($removed !== []) {
            $this->tags()->whereIn('name', $removed)->delete();
        }

        $added = array_diff($wanted, $current);
        if ($added !== []) {
            $this->tags()->createMany(array_map(
                static fn (string $name) => ['name' => $name],
                array_values($added),
            ));
        }

        $this->unsetRelation('tags');
    }

    /**
     * Rapikan nilai tag yang diketik pengguna: buang spasi berlebih dan nilai
     * kosong, lalu buang kembar tanpa memandang besar-kecil huruf ("Digital"
     * dan "digital" dianggap tag yang sama, yang pertama yang dipakai).
     *
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public static function normalizeTagNames(array $names): array
    {
        $unique = [];

        foreach ($names as $name) {
            $clean = trim(preg_replace('/\s+/u', ' ', (string) $name));

            if ($clean === '') {
                continue;
            }

            $unique[mb_strtolower($clean)] ??= $clean;
        }

        return array_values($unique);
    }
}
