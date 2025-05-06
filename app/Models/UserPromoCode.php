<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPromoCode extends Model
{
    use HasFactory;
    protected $table = 'user_promo_codes';

    protected $fillable = [
        'user_id',
        'promo_code_id',
        'booking_id',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'promo_code_id' => 'integer',
        'booking_id' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => 'string',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Optional: booking relationship if applicable
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
