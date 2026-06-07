<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'previous_gross_salary',
        'new_gross_salary',
        'previous_structure_id',
        'new_structure_id',
        'revised_by',
        'effective_date',
        'remarks',
    ];

    protected $casts = [
        'previous_gross_salary' => 'decimal:2',
        'new_gross_salary' => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function previousStructure()
    {
        return $this->belongsTo(SalaryStructure::class, 'previous_structure_id');
    }

    public function newStructure()
    {
        return $this->belongsTo(SalaryStructure::class, 'new_structure_id');
    }

    public function revisedBy()
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}
