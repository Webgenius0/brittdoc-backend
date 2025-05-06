<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPlatformFee extends Model
{
    protected $table = 'booking_platform_fees';

    protected $fillable = [
        'booking_id',
        'platform_setting_id',
        'key',
        'value',
    ];
    protected $casts = [
        'booking_id' => 'integer',
        'platform_fee_id' => 'integer',
        'key' => 'string',
        'value' => 'float',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function platformSetting()
    {
        return $this->belongsTo(PlatformSetting::class);
    }
}
