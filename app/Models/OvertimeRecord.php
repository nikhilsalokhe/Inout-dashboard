<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'overtime_type',
        'hours',
        'status',
        'manager_id',
        'hr_id',
        'amount',
        'notes',
        'is_manual_request',
        'payroll_id',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_manual_request' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function hr()
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
