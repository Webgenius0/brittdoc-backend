<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'amount', 'status'];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'float',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
