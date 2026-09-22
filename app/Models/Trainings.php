<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainings extends Model
{
    use HasFactory;

    protected $table = 'trainings';

    protected $fillable = [
        'vendor_id',
        'name',
        'hr_development_type',
        'competency_type',
        'learning_sector',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function realizations()
    {
        return $this->hasMany(TrainingRealizations::class, 'training_id');
    }
}
