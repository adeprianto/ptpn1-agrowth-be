<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetencyGroups extends Model
{
    protected $table = 'competency_groups';

    protected $fillable = ['name'];

    public function competencies(): HasMany
    {
        return $this->hasMany(Competencies::class, 'competency_group_id')->orderBy('code');
    }
}
