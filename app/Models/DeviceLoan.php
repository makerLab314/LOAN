<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'borrower_name',
        'quantity',
        'loan_start_date',
        'loan_end_date',
        'loan_purpose',
        'loaned_by',
    ];

    protected $casts = [
        'loan_start_date' => 'date',
        'loan_end_date' => 'date',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function loanedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loaned_by');
    }
}
