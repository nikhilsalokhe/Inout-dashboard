<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_name',
        'status',
        'attendance_method',
    ];

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function employees()
    {
        return $this->hasMany(User::class);
    }

    public function overtimePolicies()
    {
        return $this->morphMany(OvertimePolicyAssignment::class, 'assignable');
    }
}
