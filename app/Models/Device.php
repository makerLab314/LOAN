<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string|null $inventory_number
 * @property string $title
 * @property string|null $description
 * @property string|null $image
 * @property int|null $category_id
 * @property int $total_quantity
 * @property-read int $loaned_quantity
 * @property-read int $available_quantity
 * @property-read string $status
 */
class Device extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_LOANED = 'loaned';

    protected $fillable = [
        'inventory_number',
        'title',
        'description',
        'image',
        'category_id',
        'total_quantity',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /** @return HasMany<DeviceLoan, $this> */
    public function loans(): HasMany
    {
        return $this->hasMany(DeviceLoan::class);
    }

    /** @return HasMany<DeviceLoan, $this> */
    public function activeLoans(): HasMany
    {
        return $this->loans()->whereNull('returned_at');
    }

    /** @return HasMany<DeviceReservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(DeviceReservation::class);
    }

    /** @return HasMany<DeviceReservation, $this> */
    public function activeReservations(): HasMany
    {
        return $this->reservations()->whereIn('status', DeviceReservation::ACTIVE_STATUSES);
    }

    /** @return HasMany<DeviceAccessory, $this> */
    public function accessories(): HasMany
    {
        return $this->hasMany(DeviceAccessory::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Lädt die aktuell verliehene Menge per Subquery (vermeidet N+1).
     */
    public function scopeWithLoanedQuantity(Builder $query): Builder
    {
        if (is_null($query->getQuery()->columns)) {
            $query->select($query->getModel()->getTable().'.*');
        }

        return $query->addSelect(['loaned_quantity_sum' => DeviceLoan::query()
            ->selectRaw('COALESCE(SUM(quantity - returned_quantity), 0)')
            ->whereColumn('device_loans.device_id', 'devices.id')
            ->whereNull('returned_at'),
        ]);
    }

    public function getLoanedQuantityAttribute(): int
    {
        if (array_key_exists('loaned_quantity_sum', $this->attributes)) {
            return (int) $this->attributes['loaned_quantity_sum'];
        }

        if ($this->relationLoaded('activeLoans')) {
            return (int) $this->activeLoans->sum(fn (DeviceLoan $l) => $l->outstanding_quantity);
        }

        return (int) $this->activeLoans()->sum(DB::raw('quantity - returned_quantity'));
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->total_quantity - $this->loaned_quantity);
    }

    public function getStatusAttribute(): string
    {
        $loaned = $this->loaned_quantity;

        return match (true) {
            $loaned <= 0 => self::STATUS_AVAILABLE,
            $loaned >= $this->total_quantity => self::STATUS_LOANED,
            default => self::STATUS_PARTIAL,
        };
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function getThumbUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        $thumb = dirname($this->image).'/thumbs/'.basename($this->image);

        return Storage::disk('public')->exists($thumb)
            ? Storage::disk('public')->url($thumb)
            : $this->image_url;
    }

    /**
     * Etiketten-ID eines einzelnen Exemplars, z. B. KAB-0042-3.
     */
    public function unitLabel(int $unit): string
    {
        return ($this->inventory_number ?? 'ID'.$this->id).'-'.$unit;
    }

    /**
     * @return list<string>
     */
    public function unitLabels(): array
    {
        return array_map(fn (int $n) => $this->unitLabel($n), range(1, max(1, $this->total_quantity)));
    }

    /**
     * Exemplar-Nummern, die gerade in aktiven Ausleihen stecken.
     *
     * @return array<int, DeviceLoan> unit_no => Ausleihe
     */
    public function loanedUnits(): array
    {
        return DeviceLoanUnit::query()
            ->with('loan')
            ->where('device_id', $this->id)
            ->whereNull('returned_at')
            ->get()
            ->mapWithKeys(fn (DeviceLoanUnit $u) => [$u->unit_no => $u->loan])
            ->all();
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
