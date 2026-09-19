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
        'training_id',
        'training_start_date', 
        'training_end_date', 
        'learning_hours', 
        'cost',
        'employee_id', 
        'position_title_id', 
        'entity_id', 
        'organization_id',
        'employee_name', 
        'employee_position', 
        'employee_bod_level',
        'employee_unit', 
        'employee_division', 
        'employee_region',
    ];

    protected $casts = [
        'training_start_date' => 'date',
        'training_end_date' => 'date',
        'learning_hours' => 'integer',
        'cost' => 'integer',
        'employee_bod_level' => 'integer',
    ];

    public function realization()
    {
        return $this->belongsTo(TrainingRealizations::class, 'training_realization_id');
    }

    public function training()
    {
        return $this->belongsTo(Trainings::class, 'training_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employees::class, 'employee_id');
    }

    public function positionTitle()
    {
        return $this->belongsTo(PositionTitle::class, 'position_title_id');
    }

    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}