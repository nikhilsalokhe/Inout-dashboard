<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    // Disabling default updated_at since it only has created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'old_data',
        'new_data',
        'ip_address',
        'device_info',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
