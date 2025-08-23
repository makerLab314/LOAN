<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('device_reservations', function (Blueprint $table) {
            $table->string('reserved_by_name', 255)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('device_reservations', function (Blueprint $table) {
            $table->dropColumn('reserved_by_name');
        });
    }
};
