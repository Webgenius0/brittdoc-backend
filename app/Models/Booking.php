<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Editor\Fields\Hidden;

class Booking extends Model
{
    protected $guarded = ['id'];

    // protected  $hidden = [
    //     'category',
    // ];

    protected $casts = [
        'custom_Booking' => 'boolean'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
