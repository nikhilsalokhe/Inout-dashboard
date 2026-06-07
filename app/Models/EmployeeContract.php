<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeContract extends Model
{
    use HasFactory;

    protected $table = 'employee_contracts';

    protected $fillable = [
        'employee_id',
        'contract_start_date',
        'contract_end_date',
        'renewal_option',
        'contract_status',
        'remarks',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'renewal_option' => 'boolean',
    ];

    /**
     * Get the employee associated with the contract.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Scope contracts expiring within the specified days.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('contract_status', 'active')
            ->whereBetween('contract_end_date', [
                Carbon::today(),
                Carbon::today()->addDays($days)
            ]);
    }
}
