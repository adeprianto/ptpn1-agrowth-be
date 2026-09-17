<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nama',
        'level', 
        'organization_type_id', 
        'entity_id', 
        'job_function_id', 
        'parent_id',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function organizationType()
    {
        return $this->belongsTo(OrganizationType::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entities::class);
    }

    public function jobFunction()
    {
        return $this->belongsTo(JobFunctions::class);
    }

    public function parent()
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    public function positionTitles()
    {
        return $this->hasMany(PositionTitle::class);
    }
}