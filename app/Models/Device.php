<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'group',
        'image',
        'status',
        'borrower_name',
        'loan_start_date',
        'loan_end_date',
        'category_id',
        'loan_purpose',
        'total_quantity',
        'loaned_quantity',
    ];

    /**
     * Get the available quantity for loan.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->total_quantity - $this->loaned_quantity);
    }

    public function reservations()
    {
        return $this->hasMany(\App\Models\DeviceReservation::class);
    }

    public function loans()
    {
        return $this->hasMany(\App\Models\DeviceLoan::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }
}
