<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wurde das Gerät beim Ausleihen gescannt (oder seine ID eingegeben)? Nur dann
 * verlangt der Kiosk auch zur Rückgabe einen Scan. Bestehende Ausleihen: nein.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_loans', function (Blueprint $table) {
            $table->boolean('checkout_scanned')->default(false)->after('loaned_by');
        });
    }

    public function down(): void
    {
        Schema::table('device_loans', function (Blueprint $table) {
            $table->dropColumn('checkout_scanned');
        });
    }
};
