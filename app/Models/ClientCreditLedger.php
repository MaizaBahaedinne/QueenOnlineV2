<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientCreditLedger extends Model
{
    protected $fillable = [
        'client_id',
        'reservation_id',
        'user_id',
        'type',
        'amount',
        'related_client_id',
        'description',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function relatedClient()
    {
        return $this->belongsTo(Client::class, 'related_client_id');
    }
}
