<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planing extends Model
{
    protected $guarded = ['id'];


    // Column casts for type safety
    // protected $casts = [
    //     'user_id' => 'integer',
    //     'package_id' => 'integer',
    //     'start_date' => 'date',
    //     'end_date' => 'date',
    //     'status' => 'string',
    // ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->hasMany(Subscription::class);
    }
}
