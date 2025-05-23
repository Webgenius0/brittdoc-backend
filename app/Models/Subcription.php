<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcription extends Model
{
    // Define the table name if it's not the default
    protected $table = 'subscriptions';

    // Fillable columns for mass assignment
    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'status'
    ];

    // Column casts for type safety
    protected $casts = [
        'user_id' => 'integer',
        'package_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'string',
    ];



    // A Subscription belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class); // Foreign key 'user_id'
    }


    public function package()
    {
        return $this->belongsTo(Package::class); // Foreign key 'package_id'
    }
}
