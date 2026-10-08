<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/** Program pengembangan berjenjang, mis. PLDP I sampai PLDP IV. */
class DevelopmentPrograms extends Model
{
    use HasFactory;

    protected $table = 'development_programs';

    protected $fillable = [
        'name',
        'code',
        'has_sectors',
    ];

    protected $casts = [
        'has_sectors' => 'boolean',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(ProgramBatches::class, 'development_program_id');
    }

    public function participants(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProgramBatchParticipants::class,
            ProgramBatches::class,
            'development_program_id',
            'program_batch_id',
        );
    }

    public static function codeFromName(string $name): ?string
    {
        return preg_match('/\(([^()]+)\)\s*$/', $name, $match) ? trim($match[1]) : null;
    }
}
