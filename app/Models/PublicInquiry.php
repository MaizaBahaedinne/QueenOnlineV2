<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicInquiry extends Model
{
    protected $fillable = [
        'request_type',
        'full_name',
        'phone',
        'email',
        'service_slug',
        'event_date',
        'guest_count',
        'budget',
        'message',
        'source_page',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'guest_count' => 'integer',
        'budget' => 'decimal:2',
    ];
}