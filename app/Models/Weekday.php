<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Weekday extends Model
{
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the event associated with the weekday.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
   
    // hidden
    protected $hidden = [
        'event_id',
        'venue_id',
        'created_at',
        'updated_at',
    ];
}
