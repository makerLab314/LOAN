<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zubehör, das zu einem Gerät gehört (z. B. Kaltgerätekabel beim Lautsprecher).
 *
 * @property int $id
 * @property int $device_id
 * @property string $name
 * @property int $quantity
 * @property int $position
 */
class DeviceAccessory extends Model
{
    protected $fillable = ['device_id', 'name', 'quantity', 'position'];

    protected $casts = [
        'quantity' => 'integer',
        'position' => 'integer',
    ];

    /** @return BelongsTo<Device, $this> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
