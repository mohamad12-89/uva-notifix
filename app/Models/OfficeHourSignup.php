<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeHourSignup extends Model
{
    protected $fillable = [
        'office_hour_id',
        'student_name',
        'student_email',
        'checked_in_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];
}
