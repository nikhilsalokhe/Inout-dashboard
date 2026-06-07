<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'gross_salary',
        'basic_salary',
        'hra',
        'da',
        'travel_allowance',
        'special_allowance',
        'overtime_hours',
        'overtime_amount',
        'bonus',
        'incentives',
        'absent_deduction',
        'half_day_deduction',
        'late_penalty',
        'pf',
        'esic',
        'professional_tax',
        'tds',
        'loan_deduction',
        'advance_salary',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'payable_days',
        'paid_days',
        'status',
        'generated_at',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'basic_salary' => 'decimal:2',
        'hra' => 'decimal:2',
        'da' => 'decimal:2',
        'travel_allowance' => 'decimal:2',
        'special_allowance' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'incentives' => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'half_day_deduction' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'pf' => 'decimal:2',
        'esic' => 'decimal:2',
        'professional_tax' => 'decimal:2',
        'tds' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'advance_salary' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payable_days' => 'decimal:1',
        'paid_days' => 'decimal:1',
        'generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
