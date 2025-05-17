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
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }



    public function getImageAttribute($value): array|null
    {
        $images = json_decode($value, true);

        if (!is_array($images)) {
            return null;
        }

        return array_map(function ($image) {
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }

            if (request()->is('api/*') && !empty($image)) {
                return url($image); // Return full URL for API
            }

            return $image; // Return path for web
        }, $images);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
