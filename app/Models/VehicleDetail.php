<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleDetail extends Model
{
    protected $table = 'vehicle_details';
    protected $fillable = [
        'user_id',
        'registration_number',
        'type',
        'make',
        'model',
        'license_plate_number_eng',
        'license_plate_number_ara',
        'status'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'registration_number' => 'string',
        'type' => 'string',
        'make' => 'string',
        'model' => 'string',
        'license_plate_number_eng' => 'string',
        'license_plate_number_ara' => 'string',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->hasMany(Booking::class);
    }
}
