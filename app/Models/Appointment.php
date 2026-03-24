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
        'comments',
    ];
}
