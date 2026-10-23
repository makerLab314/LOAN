<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Inventar-IDs (Kategorie-Kürzel + Nummer, z. B. KAB-0042), Zubehör-Checklisten
 * und optionale Exemplar-Nummern (KAB-0042-3) bei Ausleihen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('code', 8)->nullable()->unique()->after('slug');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->string('inventory_number', 32)->nullable()->unique()->after('id');
        });

        Schema::create('device_accessories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('device_loan_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('unit_no');
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'unit_no', 'returned_at']);
        });

        Schema::table('device_loans', function (Blueprint $table) {
            $table->json('accessories')->nullable()->after('notes');
            $table->json('return_issues')->nullable()->after('accessories');
            $table->timestamp('issues_resolved_at')->nullable()->after('return_issues');
            $table->foreignId('issues_resolved_by')->nullable()->after('issues_resolved_at')
                ->constrained('users')->nullOnDelete();
            $table->unsignedInteger('extension_count')->default(0)->after('issues_resolved_by');
        });

        $this->assignCodesAndNumbers();
    }

    private function assignCodesAndNumbers(): void
    {
        $used = [];
        foreach (DB::table('categories')->orderBy('id')->get() as $category) {
            $base = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', Str::ascii($category->name)) ?: 'GER', 0, 3));
            $base = str_pad($base, 3, 'X');
            $code = $base;
            $i = 2;
            while (in_array($code, $used, true)) {
                $code = substr($base, 0, 2).$i++;
            }
            $used[] = $code;
            DB::table('categories')->where('id', $category->id)->update(['code' => $code]);
        }

        $codes = DB::table('categories')->pluck('code', 'id');
        $counters = [];
        foreach (DB::table('devices')->orderBy('id')->get(['id', 'category_id']) as $device) {
            $prefix = $codes[$device->category_id] ?? 'GER';
            $counters[$prefix] = ($counters[$prefix] ?? 0) + 1;
            DB::table('devices')->where('id', $device->id)->update([
                'inventory_number' => sprintf('%s-%04d', $prefix, $counters[$prefix]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('device_loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issues_resolved_by');
            $table->dropColumn(['accessories', 'return_issues', 'issues_resolved_at', 'extension_count']);
        });
        Schema::dropIfExists('device_loan_units');
        Schema::dropIfExists('device_accessories');
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique(['inventory_number']);
            $table->dropColumn('inventory_number');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
