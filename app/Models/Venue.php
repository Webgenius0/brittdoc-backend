<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{


    protected $guarded = ['id'];


    protected $casts = [
        'image' => 'array',
        'available_date' => 'date',
        'available_start_time' => 'datetime:H:i',
        'available_end_time' => 'datetime:H:i'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
