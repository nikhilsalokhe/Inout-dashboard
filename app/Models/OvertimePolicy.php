<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'calc_daily',
        'daily_min_hours',
        'daily_threshold',
        'max_daily',
        'daily_rate_multiplier',
        'calc_weekly',
        'weekly_threshold',
        'max_weekly',
        'weekly_rate_multiplier',
        'calc_monthly',
        'monthly_threshold',
        'max_monthly',
        'monthly_rate_multiplier',
        'calc_weekend',
        'weekend_rate_multiplier',
        'calc_holiday',
        'holiday_rate_multiplier',
        'rate_type',
        'fixed_rate',
        'max_payable_hours_per_month',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'calc_daily' => 'boolean',
        'calc_weekly' => 'boolean',
        'calc_monthly' => 'boolean',
        'calc_weekend' => 'boolean',
        'calc_holiday' => 'boolean',
        'daily_min_hours' => 'decimal:2',
        'daily_threshold' => 'decimal:2',
        'max_daily' => 'decimal:2',
        'daily_rate_multiplier' => 'decimal:2',
        'weekly_threshold' => 'decimal:2',
        'max_weekly' => 'decimal:2',
        'weekly_rate_multiplier' => 'decimal:2',
        'monthly_threshold' => 'decimal:2',
        'max_monthly' => 'decimal:2',
        'monthly_rate_multiplier' => 'decimal:2',
        'weekend_rate_multiplier' => 'decimal:2',
        'holiday_rate_multiplier' => 'decimal:2',
        'fixed_rate' => 'decimal:2',
        'max_payable_hours_per_month' => 'decimal:2',
    ];

    public function assignments()
    {
        return $this->hasMany(OvertimePolicyAssignment::class, 'policy_id');
    }
}
