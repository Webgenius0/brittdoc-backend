<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    // Define the table name if it's not the default
    protected $table = 'payments';

    // Fillable columns for mass assignment
    protected $fillable = [
        'booking_id',
        'user_id',
        'transaction_id',
        'promo_code_id',
        'promo_code',
        'transaction_number',
        'payment_method',
        'payment_intent_id',
        'client_secret',
        'amount',
        'status'
    ];

    // Column casts for type safety
    protected $casts = [
        'booking_id' => 'integer',
        'user_id' => 'integer',
        'transaction_id' => 'integer',
        'promo_code_id' => 'integer',
        'promo_code' => 'string',
        'transaction_number' => 'string',
        'payment_method' => 'string',
        'payment_intent_id' => 'string',
        'client_secret' => 'string',
        'amount' => 'decimal:2',
        'status' => 'string',
    ];



    // A Payment belongs to a Booking
    public function booking()
    {
        return $this->belongsTo(Booking::class); // Foreign key 'booking_id'
    }

    // A Payment belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class); // Foreign key 'user_id'
    }

    // A Payment belongs to a Transaction
    public function transaction()
    {
        return $this->belongsTo(Transaction::class); // Foreign key 'transaction_id'
    }

    public function isPaid()
    {
        return $this->status === 'success';
    }
   
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
