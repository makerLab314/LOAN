<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action', 64)->index();
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['subject_type', 'subject_id']);
        });

        // Bisherige Geräte-Historie übernehmen. Die alten Zeitstempel wurden
        // mit Zeitzone UTC gespeichert – in die App-Zeitzone umrechnen.
        if (Schema::hasTable('device_histories')) {
            $tz = config('app.timezone');
            $titles = DB::table('devices')->pluck('title', 'id');

            DB::table('device_histories')->orderBy('id')->chunk(500, function ($rows) use ($tz, $titles) {
                $insert = [];
                foreach ($rows as $row) {
                    $insert[] = [
                        'user_id' => null,
                        'user_name' => $row->action_by,
                        'action' => $row->action === 'returned' ? 'loan.returned' : 'loan.created',
                        'subject_type' => 'device',
                        'subject_id' => $row->device_id,
                        'subject_label' => $titles[$row->device_id] ?? null,
                        'properties' => json_encode(['borrower' => $row->user_name, 'legacy' => true]),
                        'ip_address' => null,
                        'user_agent' => null,
                        'created_at' => $row->created_at
                            ? Carbon::parse($row->created_at, 'UTC')->setTimezone($tz)->format('Y-m-d H:i:s')
                            : null,
                    ];
                }
                DB::table('audit_logs')->insert($insert);
            });

            Schema::drop('device_histories');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
    }
};
