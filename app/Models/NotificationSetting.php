<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'notification_settings';

    protected $fillable = [
        'user_id',
        'general_notification',
        'sound',
        'vibration',
        'special_offer',
        'payment',
        'app_update',
        'other',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'general_notification' => 'boolean',
        'sound' => 'boolean',
        'vibration' => 'boolean',
        'special_offer' => 'boolean',
        'payment' => 'boolean',
        'app_update' => 'boolean',
        'other' => 'boolean',
        'status' => 'string', // Casting status as string for enum handling
    ];

    // Define the inverse one-to-one relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
