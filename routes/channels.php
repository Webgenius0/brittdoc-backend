<?php

use App\Models\ChatRoom;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{receverId}', function ($user, $receverId) {
    return true;
    // return (int) $user->id === (int) $receverId;
});