<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Soft Deletes für Stammdaten (Historie bleibt erhalten)
 * - Ausleihen werden bei Rückgabe abgeschlossen statt gelöscht (Teilrückgabe)
 * - Alt-Ausleihen aus den Gerätefeldern (borrower_name, loan_*) übernehmen
 * - Rolle als String (statt Enum), Theme-Präferenz pro Nutzer
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['devices', 'rooms', 'categories', 'users', 'reservations'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('moderation')->change();
            $table->string('theme', 10)->default('system')->after('locale');
        });

        Schema::table('device_loans', function (Blueprint $table) {
            $table->unsignedInteger('returned_quantity')->default(0)->after('quantity');
            $table->timestamp('returned_at')->nullable()->after('loaned_by');
            $table->foreignId('returned_by')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            $table->foreignId('device_reservation_id')->nullable()->after('returned_by')
                ->constrained('device_reservations')->nullOnDelete();
            $table->text('notes')->nullable()->after('loan_purpose');
            $table->index('returned_at');
        });

        Schema::table('device_reservations', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });

        $this->migrateLegacyLoans();
    }

    /**
     * Vor Einführung von device_loans stand die (einzige) Ausleihe direkt am Gerät.
     * Solche Geräte haben status = 'loaned', aber keinen Eintrag in device_loans.
     */
    private function migrateLegacyLoans(): void
    {
        if (! Schema::hasColumn('devices', 'borrower_name')) {
            return;
        }

        $legacy = DB::table('devices')
            ->where('status', 'loaned')
            ->whereNotNull('borrower_name')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('device_loans')->whereColumn('device_loans.device_id', 'devices.id');
            })
            ->get();

        foreach ($legacy as $device) {
            // Altgeräte (vor der Mengenverwaltung) waren immer komplett verliehen
            $quantity = (int) ($device->loaned_quantity ?? 0) > 0
                ? (int) $device->loaned_quantity
                : max(1, (int) ($device->total_quantity ?? 1));

            DB::table('device_loans')->insert([
                'device_id' => $device->id,
                'borrower_name' => $device->borrower_name,
                'quantity' => $quantity,
                'returned_quantity' => 0,
                'loan_start_date' => $device->loan_start_date ?? now()->toDateString(),
                'loan_end_date' => $device->loan_end_date ?? $device->loan_start_date ?? now()->toDateString(),
                'loan_purpose' => $device->loan_purpose ?? null,
                'notes' => 'Aus Altdaten übernommen',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('device_loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('device_reservation_id');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropIndex(['returned_at']);
            $table->dropColumn(['returned_quantity', 'returned_at', 'notes']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme');
        });

        foreach (['devices', 'rooms', 'categories', 'users', 'reservations'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
    }
};
