<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ParagonIE\Sodium\Core\Curve25519\H;

class RestrictedWord extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];
}
