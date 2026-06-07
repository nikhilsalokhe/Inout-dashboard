<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceRecognitionLog extends Model
{
    use HasFactory;

    /**
     * Disable standard updated_at column since we only track creation time.
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'captured_image',
        'confidence_score',
        'liveness_passed',
        'status',
        'action_type',
        'remarks',
        'created_at',
    ];

    protected $casts = [
        'liveness_passed' => 'boolean',
        'confidence_score' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user targeted by the face recognition check.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
