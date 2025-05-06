<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    // Define the table name if it's not the default
    protected $table = 'contact_us';

    // Fillable columns for mass assignment
    protected $fillable = [
        'first_name',
        'last_name',
        'ip_address',
        'email',
        'phone',
        'message'
    ];

    // Column casts for type safety
    protected $casts = [
        'first_name' => 'string',
        'last_name' => 'string',
        'ip_address' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'message' => 'string',
    ];
}
