<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'structure_name',
        'basic_percentage',
        'hra_percentage',
        'da_percentage',
        'travel_allowance',
        'pf_enabled',
        'esic_enabled',
        'professional_tax',
        'status',
    ];

    protected $casts = [
        'basic_percentage' => 'decimal:2',
        'hra_percentage' => 'decimal:2',
        'da_percentage' => 'decimal:2',
        'travel_allowance' => 'decimal:2',
        'pf_enabled' => 'boolean',
        'esic_enabled' => 'boolean',
        'professional_tax' => 'decimal:2',
    ];

    public function employeeSalaries()
    {
        return $this->hasMany(EmployeeSalary::class);
    }
}
