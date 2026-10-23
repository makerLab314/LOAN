<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Raumbuchungen: start_at/end_at (DateTime) statt getrennter Datums-/Zeitfelder
 *   → mehrtägige Buchungen & saubere Überschneidungsprüfung
 * - Altspalten entfernen (rooms.new_column/new_description, devices.group/status/borrower_* …)
 *   Der Geräte-Status wird künftig aus den Ausleihen berechnet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable()->after('user_id');
            $table->dateTime('end_at')->nullable()->after('start_at');
        });

        DB::table('reservations')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $r) {
                $startTime = substr((string) ($r->start_time ?? '00:00'), 0, 5);
                $endTime = substr((string) ($r->end_time ?? '23:59'), 0, 5);
                DB::table('reservations')->where('id', $r->id)->update([
                    'start_at' => substr((string) $r->start_date, 0, 10).' '.$startTime.':00',
                    'end_at' => substr((string) $r->end_date, 0, 10).' '.$endTime.':00',
                ]);
            }
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'start_time', 'end_date', 'end_time']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable(false)->change();
            $table->dateTime('end_at')->nullable(false)->change();
            $table->string('purpose', 255)->nullable()->change();
            $table->index(['room_id', 'start_at', 'end_at']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['new_column', 'new_description']);
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'group', 'status', 'borrower_name', 'loan_start_date',
                'loan_end_date', 'loan_purpose', 'loaned_quantity',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('group')->nullable();
            $table->string('status')->default('available');
            $table->string('borrower_name')->nullable();
            $table->date('loan_start_date')->nullable();
            $table->date('loan_end_date')->nullable();
            $table->string('loan_purpose')->nullable();
            $table->unsignedInteger('loaned_quantity')->default(0);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('new_column')->nullable();
            $table->text('new_description')->nullable();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'start_at', 'end_at']);
            $table->date('start_date')->nullable();
            $table->time('start_time')->nullable();
            $table->date('end_date')->nullable();
            $table->time('end_time')->nullable();
        });

        DB::table('reservations')->update([
            'start_date' => DB::raw('DATE(start_at)'),
            'end_date' => DB::raw('DATE(end_at)'),
        ]);

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at']);
        });
    }
};
