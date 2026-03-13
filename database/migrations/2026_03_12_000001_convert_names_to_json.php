<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sets', function (Blueprint $table) {
            $table->text('name')->change();
        });

        Schema::table('themes', function (Blueprint $table) {
            $table->text('name')->change();
        });

        DB::table('sets')->whereNotNull('name')->orderBy('id')->chunk(1000, function ($sets) {
            foreach ($sets as $set) {
                $decoded = json_decode($set->name, true);
                if (is_array($decoded) && isset($decoded['en'])) {
                    continue;
                }

                DB::table('sets')->where('id', $set->id)->update([
                    'name' => json_encode(['en' => $set->name]),
                ]);
            }
        });

        DB::table('themes')->whereNotNull('name')->orderBy('id')->chunk(1000, function ($themes) {
            foreach ($themes as $theme) {
                $decoded = json_decode($theme->name, true);
                if (is_array($decoded) && isset($decoded['en'])) {
                    continue;
                }

                DB::table('themes')->where('id', $theme->id)->update([
                    'name' => json_encode(['en' => $theme->name]),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('sets')->whereNotNull('name')->orderBy('id')->chunk(1000, function ($sets) {
            foreach ($sets as $set) {
                $decoded = json_decode($set->name, true);
                $plainName = is_array($decoded) ? ($decoded['en'] ?? $set->name) : $set->name;
                DB::table('sets')->where('id', $set->id)->update(['name' => $plainName]);
            }
        });

        DB::table('themes')->whereNotNull('name')->orderBy('id')->chunk(1000, function ($themes) {
            foreach ($themes as $theme) {
                $decoded = json_decode($theme->name, true);
                $plainName = is_array($decoded) ? ($decoded['en'] ?? $theme->name) : $theme->name;
                DB::table('themes')->where('id', $theme->id)->update(['name' => $plainName]);
            }
        });

        Schema::table('sets', function (Blueprint $table) {
            $table->string('name')->change();
        });

        Schema::table('themes', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};
