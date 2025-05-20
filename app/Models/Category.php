<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = ['id'];


    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function venues()
    {
        return $this->hasMany(Venue::class);
    }

    public function venue_holder($query)
    {
        return $query->where('type', 'venue_holder');
    }

    public function getImageAttribute($value): string|null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        // Check if the request is an API request
        if (request()->is('api/*') && !empty($value)) {
            // Return the full URL for API requests
            return url($value);
        }

        // Return only the path for web requests
        return $value;
    }
}
