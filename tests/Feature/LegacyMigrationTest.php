<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Spielt das alte Schema mit typischen Altdaten ein und prüft,
 * dass die neuen Migrationen alles korrekt übernehmen.
 */
class LegacyMigrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Artisan::call('migrate:fresh');
        parent::tearDown();
    }

    public function test_legacy_data_is_migrated(): void
    {
        Artisan::call('db:wipe');

        // 1) Nur die alten Migrationen (vor 2026) ausführen
        foreach (collect(File::files(database_path('migrations')))->sortBy->getFilename() as $file) {
            if (str_starts_with($file->getFilename(), '2026_')) {
                continue;
            }
            Artisan::call('migrate', ['--path' => 'database/migrations/'.$file->getFilename(), '--force' => true]);
        }
        $this->assertTrue(Schema::hasColumn('devices', 'borrower_name'));

        // 2) Altdaten wie auf dem Server
        $now = '2025-11-01 10:00:00';
        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'email' => 'a@example.org', 'password' => 'x', 'role' => 'administration', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Kabel', 'slug' => 'kabel', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->insert(['id' => 2, 'name' => 'Kamera', 'slug' => 'kamera', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('devices')->insert([
            // Alt-Ausleihe direkt am Gerät, ohne device_loans
            ['id' => 1, 'title' => 'Canon', 'group' => 'Kamera', 'category_id' => 2, 'status' => 'loaned', 'borrower_name' => 'Herr Alt',
                'loan_start_date' => '2025-10-30', 'loan_end_date' => '2025-11-05', 'loan_purpose' => 'Projekt', 'total_quantity' => 1, 'loaned_quantity' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'HDMI', 'group' => 'Kabel', 'category_id' => 1, 'status' => 'available', 'borrower_name' => null,
                'loan_start_date' => null, 'loan_end_date' => null, 'loan_purpose' => null, 'total_quantity' => 10, 'loaned_quantity' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'title' => 'Sony', 'group' => 'Kamera', 'category_id' => 2, 'status' => 'available', 'borrower_name' => null,
                'loan_start_date' => null, 'loan_end_date' => null, 'loan_purpose' => null, 'total_quantity' => 1, 'loaned_quantity' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('device_histories')->insert(['device_id' => 1, 'action' => 'loaned', 'user_name' => 'Herr Alt (1 Stück)', 'action_by' => 'Admin', 'created_at' => '2025-10-30 08:00:00', 'updated_at' => $now]);
        DB::table('rooms')->insert(['id' => 1, 'name' => 'Aula', 'description' => 'Groß', 'location' => 'EG', 'new_column' => 'Standardwert', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('reservations')->insert(['room_id' => 1, 'user_id' => 1, 'start_date' => '2025-11-03', 'start_time' => '14:00', 'end_date' => '2025-11-04', 'end_time' => '10:00', 'purpose' => 'Tagung', 'created_at' => $now, 'updated_at' => $now]);

        // 3) Neue Migrationen
        Artisan::call('migrate', ['--force' => true]);

        // Alt-Ausleihe wurde übernommen
        $loan = DB::table('device_loans')->where('device_id', 1)->first();
        $this->assertNotNull($loan);
        $this->assertSame('Herr Alt', $loan->borrower_name);
        $this->assertSame(1, (int) $loan->quantity);
        $this->assertNull($loan->returned_at);
        $this->assertFalse(Schema::hasColumn('devices', 'borrower_name'));

        // Inventar-IDs nach Kategorie-Kürzel
        $this->assertSame('KAM', DB::table('categories')->where('id', 2)->value('code'));
        $this->assertSame('KAB', DB::table('categories')->where('id', 1)->value('code'));
        $this->assertSame(['KAM-0001', 'KAB-0001', 'KAM-0002'], DB::table('devices')->orderBy('id')->pluck('inventory_number')->all());

        // Raumbuchung mit Datum+Zeit → start_at/end_at
        $booking = DB::table('reservations')->first();
        $this->assertSame('2025-11-03 14:00:00', $booking->start_at);
        $this->assertSame('2025-11-04 10:00:00', $booking->end_at);
        $this->assertFalse(Schema::hasColumn('rooms', 'new_column'));

        // Historie im Audit-Log, Zeit von UTC in Ortszeit umgerechnet
        $log = DB::table('audit_logs')->where('action', 'loan.created')->first();
        $this->assertSame('Admin', $log->user_name);
        $this->assertSame('2025-10-30 09:00:00', $log->created_at);
        $this->assertFalse(Schema::hasTable('device_histories'));

        // Rückwärts migrieren funktioniert
        Artisan::call('migrate:rollback', ['--step' => 4, '--force' => true]);
        $this->assertTrue(Schema::hasColumn('reservations', 'start_date'));
    }
}
