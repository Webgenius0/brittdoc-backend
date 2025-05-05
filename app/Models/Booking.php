<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'unique_id',
        'parking_space_id',
        'vehicle_details_id',
        'number_of_slot',
        'pricing_type',
        'pricing_id',
        'per_hour_price',
        'estimated_hours',
        'estimated_price',
        'platform_fee',
        'total_price',
        'booking_date_start',
        'booking_date_end',
        'booking_time_start',
        'booking_time_end',
        'start_time',
        'end_time',
        'status'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'unique_id' => 'string',
        'parking_space_id' => 'integer',
        'vehicle_details_id' => 'integer',
        'number_of_slot' => 'integer',
        'pricing_type' => 'string',
        'pricing_id' => 'integer',
        'per_hour_price' => 'decimal:2',
        'estimated_hours' => 'string',
        'estimated_price' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'total_price' => 'decimal:2',
        'booking_date_start' => 'date',
        'booking_date_end' => 'date',
        'booking_time_start' => 'datetime',
        'booking_time_end' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class);
    }

    public function vehicleDetail()
    {
        return $this->belongsTo(VehicleDetail::class,'vehicle_details_id');
    }

    // Accessor for start_time (only time part)
    public function getBookingTimeStartAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }

    // Accessor for end_time (only time part)
    public function getBookingTimeEndAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }

    public function platformFee()
    {
        return $this->hasMany(BookingPlatformFee::class);
    }

    
    // Accessor for booking_date_start (only date part)
    public function getBookingDateStartAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    // Accessor for booking_date_end (only date part)
    public function getBookingDateEndAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
