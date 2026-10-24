<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string $locale
 * @property string $theme
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'administration';

    public const ROLE_MODERATOR = 'moderation';

    /** Gemeinsames Konto für ein fest montiertes Tablet: dauerhaft angemeldet, stark eingeschränkt */
    public const ROLE_KIOSK = 'kiosk';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_MODERATOR, self::ROLE_KIOSK];

    public const THEMES = ['system', 'light', 'dark'];

    /**
     * "role" ist bewusst NICHT mass-assignable – Rollen werden nur explizit gesetzt.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'theme',
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

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKiosk(): bool
    {
        return $this->role === self::ROLE_KIOSK;
    }

    /** @return HasMany<RoomBooking, $this> */
    public function roomBookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class);
    }

    /** @return HasMany<DeviceReservation, $this> */
    public function deviceReservations(): HasMany
    {
        return $this->hasMany(DeviceReservation::class);
    }

    public static function activeAdminCount(): int
    {
        return static::query()->where('role', self::ROLE_ADMIN)->count();
    }
}
