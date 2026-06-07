<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_name',
        'shift_type',
        'start_time',
        'end_time',
        'grace_time_minutes',
        'half_day_time',
        'minimum_working_hours',
        'weekly_off_days',
        'status',
    ];

    /**
     * Get the assignments made for this shift.
     */
    public function assignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /**
     * Get the attendances registered under this shift.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
