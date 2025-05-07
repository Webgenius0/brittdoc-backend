<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    // Define the table name if it's not the default
    protected $table = 'pakages';

    // Fillable columns for mass assignment
    protected $fillable = [
        'title',
        'description',
        'price',
        'billing_cycle',
        'duration',
        'status'
    ];

    protected $casts = [
        'title' => 'string',
        'description' => 'string',
        'billing_cycle' => 'string',
        'price' => 'decimal:2',
        'duration' => 'integer',
        'status' => 'string',
    ];


    public function subscriptions()
    {
        return $this->hasMany(Subcription::class);
    }
}
