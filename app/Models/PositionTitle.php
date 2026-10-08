<?php

namespace App\Models;

use App\Enums\BodLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PositionTitle extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'code', 
        'name', 
        'name_sap', 
        'level_bod', 
        'job_group_id', 
        'job_function_id',
    ];

    protected $casts = [
        'level_bod' => BodLevel::class,
    ];

    public function jobGroup()
    {
        return $this->belongsTo(JobGroups::class);
    }

    public function jobFunction()
    {
        return $this->belongsTo(JobFunctions::class);
    }

    public function employees()
    {
        return $this->hasMany(Employees::class);
    }

    /** Kompetensi yang dibutuhkan jabatan ini beserta level minimalnya (`required_level`). */
    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competencies::class, 'position_title_competencies', 'position_title_id', 'competency_id')
            ->using(PositionTitleCompetencies::class)
            ->withPivot('required_level')
            ->withTimestamps();
    }
}