<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = ['id'];


    public function venues()
    {
        return $this->hasMany(Venue::class);
    }

    public function venue_holder($query)
    {
        return $query->where('type', 'venue_holder');
    }
}
