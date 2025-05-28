<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'title' => 'string',
        'description' => 'string',
        'billing_cycle' => 'string',
        'price' => 'decimal:2',
        'duration' => 'integer',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class); 
    }

    public function planing()
    {
        return $this->belongsTo(Planing::class);
    }
}
