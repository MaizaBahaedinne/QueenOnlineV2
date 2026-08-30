<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ServiceAffectation extends Model
{
    protected $table = 'tbl_service_affectation';

    protected $fillable = [
        'affectation',
        'user_id',
        'reservation_id',
        'created_dtm',
        'created_by',
        'is_chef',
    ];

    protected function casts(): array
    {
        return [
            'created_dtm' => 'datetime',
            'is_chef' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $reservationId = (int) $model->reservation_id;
            if ($reservationId <= 0) {
                return;
            }

            $isSalleReservation = Reservation::query()
                ->where('id', $reservationId)
                ->where(function ($query) {
                    $query->where('service_slug', 'salles')->orWhereNull('service_slug');
                })
                ->exists();

            if (! $isSalleReservation) {
                throw ValidationException::withMessages([
                    'reservation_id' => 'Les affectations de services sont reservees aux reservations de type salle.',
                ]);
            }
        });
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}