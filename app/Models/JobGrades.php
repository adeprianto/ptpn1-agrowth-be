<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobGrades extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function positions()
    {
        return $this->hasMany(PositionTitle::class, 'job_grade_id');
    }
}
