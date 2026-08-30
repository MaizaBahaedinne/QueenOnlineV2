<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ServiceEntree extends Model
{
    protected $table = 'tbl_services_entrees';

    protected $fillable = [
        'reservation_id',
        'quantite',
        'nature',
        'moment_service',
        'heure_prevu',
        'is_deleted',
        'note',
        'created_by',
        'created_dtm',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'is_deleted' => 'boolean',
            'created_dtm' => 'datetime',
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
                    'reservation_id' => 'Les entrees de services sont reservees aux reservations de type salle.',
                ]);
            }
        });
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function retours()
    {
        return $this->hasMany(ServiceRetour::class, 'entree_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}