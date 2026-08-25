<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'phone',
        'cin',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function canFeature(string $moduleSlug, string $featureSlug, string $action): bool
    {
        if (! in_array($action, ['view', 'create', 'update', 'delete'], true)) {
            return false;
        }

        if (! $this->role_id) {
            return false;
        }

        if (! Schema::hasTable('modules') || ! Schema::hasTable('module_features') || ! Schema::hasTable('role_feature_permissions')) {
            return true;
        }

        $permission = RoleFeaturePermission::query()
            ->where('role_id', $this->role_id)
            ->whereHas('feature', function ($query) use ($moduleSlug, $featureSlug) {
                $query->where('slug', $featureSlug)
                    ->where('is_active', true)
                    ->whereHas('module', function ($moduleQuery) use ($moduleSlug) {
                        $moduleQuery->where('slug', $moduleSlug)
                            ->where('is_active', true);
                    });
            })
            ->first();

        if (! $permission) {
            return false;
        }

        $column = 'can_' . $action;

        return (bool) $permission->{$column};
    }
}
