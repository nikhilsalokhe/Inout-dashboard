<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_policy_id',
        'total_leave',
        'used_leave',
        'remaining_leave',
        'year',
    ];

    protected $casts = [
        'total_leave' => 'decimal:2',
        'used_leave' => 'decimal:2',
        'remaining_leave' => 'decimal:2',
        'year' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leavePolicy()
    {
        return $this->belongsTo(LeavePolicy::class);
    }
}
