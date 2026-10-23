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

    /** @return HasMany<RoomBooking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class);
    }
}
