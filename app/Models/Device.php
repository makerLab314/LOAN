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
        'group',        // bleibt vorerst, bis die Spalte entfernt wird
        'image',
        'status',
        'borrower_name',
        'loan_start_date',
        'loan_end_date',
        'category_id',  // WICHTIG: neu für Mass Assignment
    ];

    public function reservations()
    {
        return $this->hasMany(\App\Models\DeviceReservation::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }
}
