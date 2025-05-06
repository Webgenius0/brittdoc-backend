<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faqs';
    protected $fillable = ['question', 'answer', 'type', 'status'];

    protected $casts = [
        'question' => 'string',
        'answer' => 'string',
        'type' => 'string',
        'status' => 'string',
    ];

}
