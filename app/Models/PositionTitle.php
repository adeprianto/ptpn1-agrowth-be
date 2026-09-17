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
        'nama', 
        'level', 
        'job_family_id', 
        'organization_id',
    ];
    
    protected $casts = [
        'level_bod' => BodLevel::class,
    ];

    public function jobFamily()
    {
        return $this->belongsTo(JobFamilies::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}