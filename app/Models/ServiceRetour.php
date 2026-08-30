<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ServiceRetour extends Model
{
    protected $table = 'tbl_services_retours';

    protected $fillable = [
        'entree_id',
        'quantite_retournee',
        'note_retour',
        'created_dtm',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantite_retournee' => 'integer',
            'created_dtm' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $entreeId = (int) $model->entree_id;
            if ($entreeId <= 0) {
                return;
            }

            $isSalleReservation = ServiceEntree::query()
                ->where('id', $entreeId)
                ->whereHas('reservation', function ($query) {
                    $query->where('service_slug', 'salles')->orWhereNull('service_slug');
                })
                ->exists();

            if (! $isSalleReservation) {
                throw ValidationException::withMessages([
                    'entree_id' => 'Les retours de services sont reserves aux reservations de type salle.',
                ]);
            }
        });
    }

    public function entree()
    {
        return $this->belongsTo(ServiceEntree::class, 'entree_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}