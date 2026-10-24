<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Name der buchenden Person – nötig, wenn am gemeinsamen Kiosk-Konto gebucht wird.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Raumbuchungen liegen historisch in der Tabelle "reservations"
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('booked_by_name', 255)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('booked_by_name');
        });
    }
};
