<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingRealizations extends Model
{
    use HasFactory;

    protected $table = 'training_realizations';

    protected $fillable = [
        'training_name', 
        'training_id',
        'training_start_date', 
        'training_end_date',
        'total_participants', 
        'total_learning_hours', 
        'cost',
    ];

    protected $casts = [
        'training_start_date' => 'date',
        'training_end_date' => 'date',
        'total_participants' => 'integer',
        'total_learning_hours' => 'integer',
        'cost' => 'integer',
    ];

    public function training()
    {
        return $this->belongsTo(Trainings::class, 'training_id');
    }

    public function details()
    {
        return $this->hasMany(TrainingRealizationDetails::class, 'training_realization_id');
    }

    // hitung ulang ringkasan dari detail peserta
    public function recalculateTotals(): void
    {
        $details = $this->details()->get();

        $this->update([
            'total_participants' => $details->count(),
            'total_learning_hours' => (int) $details->sum('learning_hours'),
            'cost' => (int) $details->sum('cost'),
        ]);
    }
}