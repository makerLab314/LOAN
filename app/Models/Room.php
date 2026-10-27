<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $location
 */
class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'location'];

    /** Anzahl der Raumfarben in app.css (.room-c0 …) */
    public const COLORS = 6;

    /** @return HasMany<RoomBooking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class);
    }

    /**
     * Farbklasse für Kalender und Belegungsleiste. Vergeben in Reihenfolge der Anlage,
     * damit sich die Farben nicht verschieben, wenn ein Raum umbenannt wird.
     */
    public function colorClass(): string
    {
        $order = self::creationOrder();

        return 'room-c'.(($order[$this->id] ?? $this->id) % self::COLORS);
    }

    /**
     * @return array<int, int> Raum-ID => Position (einmal je Request)
     */
    private static function creationOrder(): array
    {
        return once(fn () => static::withTrashed()->orderBy('id')->pluck('id')->flip()->all());
    }
}
