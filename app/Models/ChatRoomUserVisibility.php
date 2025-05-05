<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRoomUserVisibility extends Model
{
    protected $fillable = ['chat_room_id', 'user_id', 'is_visible', 'status'];

    protected $casts = [
        'status' => 'string',
        'is_visible' => 'boolean',
    ];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
