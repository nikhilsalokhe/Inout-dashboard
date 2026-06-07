<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceResetRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'old_face_image',
        'new_face_image',
        'status',
        'requested_at',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the employee who requested the face reset.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the admin who approved/rejected the face reset.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
