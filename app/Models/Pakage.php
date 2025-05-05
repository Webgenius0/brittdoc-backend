<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pakage extends Model
{
    // Define the table name if it's not the default
    protected $table = 'pakages';

    // Fillable columns for mass assignment
    protected $fillable = [
        'title',
        'description',
        'price',
        'duration',
        'status'
    ];

    protected $casts = [
        'title' => 'string',
        'description' => 'string',
        'price' => 'decimal:2',
        'duration' => 'integer',
        'status' => 'string',
    ];


    public function subscriptions()
    {
        return $this->hasMany(Subcription::class);  // Assuming a package can have many subscriptions
    }
}
