<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeHour extends Model
{
    protected $fillable = [
        'ta_name',
        'date',
        'time',
        'location',
        'attendance_count',
    ];
}
