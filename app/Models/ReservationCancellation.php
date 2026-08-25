<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationCancellation extends Model
{
    protected $fillable = [
        'reservation_id',
        'client_id',
        'user_id',
        'present_on_site',
        'termination_signed',
        'credit_amount',
        'note',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
