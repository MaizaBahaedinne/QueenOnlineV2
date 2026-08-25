<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\ClientCreditLedger;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'client_type',
        'fiscal_number',
        'company_name',
        'first_name',
        'name',
        'gender',
        'birth_date',
        'email',
        'phone',
        'phone_label_1',
        'phone_2',
        'phone_label_2',
        'cin',
        'address_number',
        'address_street',
        'date_cin',
        'city',
        'governorate',
        'source',
        'note',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function creditLedgers()
    {
        return $this->hasMany(ClientCreditLedger::class);
    }
}
