<?php

namespace App\Models;

use App\Enums\CompetencyLevel;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PositionTitleCompetencies extends Pivot
{
    protected $table = 'position_title_competencies';

    public $incrementing = true;

    protected $casts = ['required_level' => CompetencyLevel::class];
}
