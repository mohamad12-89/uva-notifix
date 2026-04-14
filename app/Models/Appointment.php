<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'student_name',
        'reason',
        'help_needed',
        'class',
        'ta_selected',
        'assigned_to_name',
        'assigned_to_email',
        'assigned_to_role',
        'preferred_date',
        'preferred_time',
        'comments',
        'status',
    ];
}
