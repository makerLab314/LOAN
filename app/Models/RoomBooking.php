<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Raumbuchung (Tabelle "reservations").
 *
 * @property int $id
 * @property int $room_id
 * @property int $user_id
 * @property string|null $booked_by_name
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property string|null $purpose
 */
class RoomBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reservations';

    protected $fillable = [
        'room_id',
        'user_id',
        'booked_by_name',
        'start_at',
        'end_at',
        'purpose',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** Am Kiosk gebucht: eingetragener Name, sonst das Konto */
    public function displayName(): string
    {
        return $this->booked_by_name ?: ($this->user?->name ?? '—');
    }

    public function scopeOverlapping(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->where('start_at', '<', $to)->where('end_at', '>', $from);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('end_at', '>', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('end_at', '<=', now());
    }
}
