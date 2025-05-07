<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'parking_space_id',
        'comment',
        'rating',
        'status'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'parking_space_id' => 'integer',
        'comment' => 'string',
        'rating' => 'integer',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSpace()
    {
        return $this->belongsTo(parkingSpace::class);
    }

    
}

