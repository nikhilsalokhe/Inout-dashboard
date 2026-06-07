<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_name',
        'leave_code',
        'leave_type',
        'total_yearly_leave',
        'monthly_credit',
        'carry_forward',
        'max_carry_forward',
        'requires_approval',
        'status',
    ];

    protected $casts = [
        'carry_forward' => 'boolean',
        'requires_approval' => 'boolean',
        'total_yearly_leave' => 'integer',
        'max_carry_forward' => 'integer',
        'monthly_credit' => 'decimal:2',
    ];

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function applications()
    {
        return $this->hasMany(LeaveApplication::class);
    }
}
