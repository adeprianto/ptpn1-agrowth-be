<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingRealizationDetails extends Model
{
    use HasFactory;

    protected $table = 'training_realization_details';

    protected $fillable = [
        'training_realization_id',
        'employee_id',
        'year',
        'month',
        'start_date',
        'end_date',
        'duration_days',
        'learning_hours_per_day',
        'experiental_learning_hours',
        'social_learning_hours',
        'formal_learning_hours',
        'duration_learning_hours',
        'learning_cost',
        'transport_cost',
        'perdiem_cost',
        'travel_expense_cost',
        'total_cost',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'year' => 'integer',
        'month' => 'integer',
        'duration_days' => 'integer',
        'learning_hours_per_day' => 'integer',
        'experiental_learning_hours' => 'integer',
        'social_learning_hours' => 'integer',
        'formal_learning_hours' => 'integer',
        'duration_learning_hours' => 'integer',
        'learning_cost' => 'integer',
        'transport_cost' => 'integer',
        'perdiem_cost' => 'integer',
        'travel_expense_cost' => 'integer',
        'total_cost' => 'integer',
    ];

    public function realization()
    {
        return $this->belongsTo(TrainingRealizations::class, 'training_realization_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employees::class, 'employee_id');
    }
}
