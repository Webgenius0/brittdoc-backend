<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    protected $fillable = [
        'user_id', 'host_id', 'status'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'host_id' => 'integer',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }
}
