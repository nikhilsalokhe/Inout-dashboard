<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'target_department_id',
        'target_location_id',
        'status',
        'created_by',
    ];

    /**
     * Get the department target for the announcement.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    /**
     * Get the location target for the announcement.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'target_location_id');
    }

    /**
     * Get the author user who created the announcement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include announcements targeted for a specific user.
     * 
     * Matches announcements where department/location are either null (global)
     * or match the given user's department/location.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('status', 'published')
            ->where(function ($q) use ($user) {
                $q->where(function ($sub) {
                    $sub->whereNull('target_department_id')
                        ->whereNull('target_location_id');
                })
                ->orWhere(function ($sub) use ($user) {
                    $sub->where('target_department_id', $user->department_id)
                        ->whereNull('target_location_id');
                })
                ->orWhere(function ($sub) use ($user) {
                    $sub->whereNull('target_department_id')
                        ->where('target_location_id', $user->location_id);
                })
                ->orWhere(function ($sub) use ($user) {
                    $sub->where('target_department_id', $user->department_id)
                        ->where('target_location_id', $user->location_id);
                });
            });
    }
}
