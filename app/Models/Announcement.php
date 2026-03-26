<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'author_name', 'office_hour_id'];

    public function officeHour()
    {
        return $this->belongsTo(OfficeHour::class);
    }
}