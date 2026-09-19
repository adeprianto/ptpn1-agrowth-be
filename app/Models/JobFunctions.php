<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobFunctions extends Model
{
    use HasFactory;

    protected $table = 'job_functions';

    protected $fillable = [
        'code',
        'name',
    ];
    
    public function organizations()
    {
        return $this->hasMany(Organization::class, 'job_function_id');
    }
    
    public function positionTitles()
    {
        return $this->hasMany(PositionTitle::class, 'job_function_id');
    }
}
