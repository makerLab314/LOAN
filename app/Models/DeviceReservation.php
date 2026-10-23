<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $device_id
 * @property int $user_id
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property string|null $purpose
 * @property int $quantity
 * @property string $status
 * @property string|null $reserved_by_name
 */
class DeviceReservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CONVERTED = 'converted';

    /** Status, die Bestand blockieren */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED];

    protected $fillable = [
        'device_id',
        'user_id',
        'start_at',
        'end_at',
        'purpose',
        'quantity',
        'status',
        'reserved_by_name',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'quantity' => 'integer',
    ];

    /** @return BelongsTo<Device, $this> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** @return HasOne<DeviceLoan, $this> */
    public function loan(): HasOne
    {
        return $this->hasOne(DeviceLoan::class, 'device_reservation_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /** Halboffenes Intervall: [start, end) überschneidet sich mit [from, to) */
    public function scopeOverlapping(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->where('start_at', '<', $to)->where('end_at', '>', $from);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function displayName(): string
    {
        return $this->reserved_by_name ?: ($this->user?->name ?? '—');
    }
}
