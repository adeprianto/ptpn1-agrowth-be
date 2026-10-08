<?php

namespace App\Models;

use App\Enums\CompetencyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyLevels extends Model
{
    protected $table = 'competency_levels';

    protected $fillable = [
        'competency_id',
        'level',
        'description'
    ];

    protected $casts = ['level' => CompetencyLevel::class];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competencies::class, 'competency_id');
    }
}
