<?php

namespace App\Models;

use App\Enums\CompetencyItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyItems extends Model
{
    protected $table = 'competency_items';

    protected $fillable = [
        'competency_id',
        'type',
        'name',
        'sort_order'
    ];

    protected $casts = ['type' => CompetencyItemType::class];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competencies::class, 'competency_id');
    }
}
