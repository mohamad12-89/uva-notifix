<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeHour extends Model
{
    protected $fillable = [
        'ta_name',
        'date',
        'time',
        'end_time',
        'duration_minutes',
        'location',
        'attendance_count',
    ];

    public function signups(): HasMany
    {
        return $this->hasMany(OfficeHourSignup::class);
    }
}
