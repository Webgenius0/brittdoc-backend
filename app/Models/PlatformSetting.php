<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $fillable = ['key', 'value'];
    protected $casts = [
        'key' => 'string',
        'value' => 'decimal:2',
    ];
}
