<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTermination extends Model
{
    use HasFactory;

    protected $table = 'employee_terminations';

    protected $fillable = [
        'employee_id',
        'termination_type',
        'termination_reason',
        'last_working_date',
        'notice_period_days',
        'exit_status',
        'final_settlement_status',
        'pending_salary',
        'leave_encashment',
        'asset_return_status',
        'exit_interview_status',
        'exit_interview_notes',
        'remarks',
        'terminated_by',
        'terminated_at',
    ];

    protected $casts = [
        'last_working_date' => 'date',
        'terminated_at' => 'datetime',
        'pending_salary' => 'decimal:2',
        'leave_encashment' => 'decimal:2',
    ];

    /**
     * Get the employee associated with the termination.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the admin who processed this termination.
     */
    public function terminatedBy()
    {
        return $this->belongsTo(User::class, 'terminated_by');
    }
}
