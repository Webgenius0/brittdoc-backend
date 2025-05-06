<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;
    protected $fillable = ['chat_room_id', 'sender_id', 'receiver_id', 'content', 'is_read', 'deleted_at', 'status'];

    protected $casts = [
        'chat_room_id' => 'integer',
        'sender_id' => 'integer',
        'receiver_id' => 'integer',
        'content' => 'string',
        'is_read' => 'boolean',
        'deleted_at' => 'datetime',
        'status' => 'string',
    ];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
