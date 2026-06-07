<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_name',
        'latitude',
        'longitude',
        'allowed_radius_meter',
        'status',
     ];

    public function employees()
    {
        return $this->hasMany(User::class);
    }

    public function permittedEmployees()
    {
        return $this->belongsToMany(User::class, 'employee_geo_locations', 'geo_location_id', 'employee_id')->withTimestamps();
    }
}
