<?php

namespace App\Models;

use App\Enums\BodLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}