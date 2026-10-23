<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $device_id
 * @property string $borrower_name
 * @property int $quantity
 * @property int $returned_quantity
 * @property Carbon $loan_start_date
 * @property Carbon $loan_end_date
 * @property string|null $loan_purpose
 * @property string|null $notes
 * @property array<int, array{name: string, quantity: int, checked: bool}>|null $accessories
 * @property array<int, array{name: string, missing: int}>|null $return_issues
 * @property Carbon|null $returned_at
 * @property Carbon|null $issues_resolved_at
 * @property-read int $outstanding_quantity
 */
class DeviceLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'borrower_name',
        'quantity',
        'returned_quantity',
        'loan_start_date',
        'loan_end_date',
        'loan_purpose',
        'notes',
        'accessories',
        'return_issues',
        'loaned_by',
        'returned_at',
        'returned_by',
        'device_reservation_id',
        'issues_resolved_at',
        'issues_resolved_by',
        'extension_count',
    ];

    protected $casts = [
        'loan_start_date' => 'date',
        'loan_end_date' => 'date',
        'returned_at' => 'datetime',
        'issues_resolved_at' => 'datetime',
        'accessories' => 'array',
        'return_issues' => 'array',
        'quantity' => 'integer',
        'returned_quantity' => 'integer',
    ];

    /** @return BelongsTo<Device, $this> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function loanedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loaned_by')->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by')->withTrashed();
    }

    /** @return BelongsTo<DeviceReservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(DeviceReservation::class, 'device_reservation_id');
    }

    /** @return HasMany<DeviceLoanUnit, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(DeviceLoanUnit::class)->orderBy('unit_no');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $this->scopeActive($query)->whereDate('loan_end_date', '<', today());
    }

    public function scopeWithOpenIssues(Builder $query): Builder
    {
        return $query->whereNotNull('return_issues')->whereNull('issues_resolved_at');
    }

    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }

    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    public function isOverdue(): bool
    {
        return $this->isActive() && $this->loan_end_date->lt(today());
    }

    public function isDueToday(): bool
    {
        return $this->isActive() && $this->loan_end_date->isToday();
    }

    public function hasOpenIssues(): bool
    {
        return ! empty($this->return_issues) && $this->issues_resolved_at === null;
    }

    /**
     * @return list<int>
     */
    public function outstandingUnitNumbers(): array
    {
        return $this->units->whereNull('returned_at')->pluck('unit_no')->map(fn ($n) => (int) $n)->values()->all();
    }
}
