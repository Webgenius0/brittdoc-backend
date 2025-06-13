<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Editor\Fields\Hidden;

class OffDay extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'unavailable_date' => 'array',
    ];


    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    protected $hidden = [
        'event_id',
        'venue_id',
        'created_at',
        'updated_at',
    ];
}
