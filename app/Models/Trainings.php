<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainings extends Model
{
    use HasFactory;

    protected $table = 'trainings';

    protected $fillable = [
        'name', 
        'activity_type', 
        'learning_sector', 
        'learning_type',
        'learning_hours', 
        'cost', 
        'organizer_id',
    ];

    protected $casts = [
        'learning_hours' => 'integer',
        'cost' => 'integer',
    ];

    public function organizer()
    {
        return $this->belongsTo(Organizers::class, 'organizer_id');
    }

    public function realizations()
    {
        return $this->hasMany(TrainingRealizations::class, 'training_id');
    }
}