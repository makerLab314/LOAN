<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Exemplar-Nummer innerhalb einer Ausleihe (nur zur Fehlererkennung,
 * z. B. "Exemplar 4 ist bereits verliehen").
 *
 * @property int $id
 * @property int $device_loan_id
 * @property int $device_id
 * @property int $unit_no
 * @property Carbon|null $returned_at
 */
class DeviceLoanUnit extends Model
{
    protected $fillable = ['device_loan_id', 'device_id', 'unit_no', 'returned_at'];

    protected $casts = [
        'unit_no' => 'integer',
        'returned_at' => 'datetime',
    ];

    /** @return BelongsTo<DeviceLoan, $this> */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(DeviceLoan::class, 'device_loan_id');
    }
}
