<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = [];



    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
