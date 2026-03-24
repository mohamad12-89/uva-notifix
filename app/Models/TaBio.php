<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaBio extends Model
{
    protected $fillable = [
        'name',
        'year',
        'major',
        'email',
        'notes',
    ];
}
