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

    public function Organization()
    {
        return $this->hasMany(Organization::class, 'function_id');
    }
}
