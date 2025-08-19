<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // 1) Spalte hinzufügen (erstmal nullable, damit wir gefahrlos befüllen können)
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();
        });

        // 2) Kategorien aus existierenden 'group'-Werten erzeugen
        $groups = DB::table('devices')
            ->select('group')
            ->whereNotNull('group')
            ->where('group', '!=', '')
            ->distinct()
            ->pluck('group');

        foreach ($groups as $g) {
            $name = trim($g);
            if ($name === '') continue;
            $exists = DB::table('categories')->where('name', $name)->exists();

            if (!$exists) {
                DB::table('categories')->insert([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3) category_id je Device setzen gemäß bisherigem 'group'-String
        $categories = DB::table('categories')->pluck('id', 'name'); // ['name' => id]

        DB::table('devices')->whereNotNull('group')->where('group', '!=', '')->orderBy('id')->chunkById(500, function ($devices) use ($categories) {
            foreach ($devices as $d) {
                $name = trim($d->group ?? '');
                $catId = $categories[$name] ?? null;
                if ($catId) {
                    DB::table('devices')->where('id', $d->id)->update(['category_id' => $catId]);
                }
            }
        });

        // Optional (später, wenn alles geprüft ist): 'group' auf nullable belassen oder entfernen.
        // Ich lasse sie vorerst stehen, damit nichts "kaputt" geht.
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
