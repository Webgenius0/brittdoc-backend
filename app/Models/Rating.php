<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $guarded = ['id'];

    //rating table ar data hide 
    protected $hidden = ['created_at', 'user_id', 'event_id', 'venue_id', 'booking_id'];


    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
