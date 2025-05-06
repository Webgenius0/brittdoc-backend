<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'booking_id', 'sender_id', 'receiver_id', 'sub_amount', 'service_fee', 'discount', 'total_amount', 'type', 'payment_gateway', 'status'
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'sender_id' => 'integer',
        'receiver_id' => 'integer',
        'sub_amount' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'type' => 'string',
        'payment_gateway' => 'string',
        'status' => 'string',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
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

