<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimePolicyAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id',
        'assignable_type',
        'assignable_id',
    ];

    public function policy()
    {
        return $this->belongsTo(OvertimePolicy::class, 'policy_id');
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}
