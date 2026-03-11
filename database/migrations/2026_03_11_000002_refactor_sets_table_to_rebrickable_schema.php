<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sets', function (Blueprint $table) {
            // Rename columns to match Rebrickable schema
            $table->renameColumn('set_number', 'set_num');
            $table->renameColumn('pieces', 'num_parts');
            $table->renameColumn('image_url', 'img_url');
        });

        Schema::table('sets', function (Blueprint $table) {
            // Replace theme string with theme_id FK
            $table->unsignedInteger('theme_id')->nullable()->after('name');
            $table->foreign('theme_id')->references('id')->on('themes')->nullOnDelete();
            $table->index('theme_id');

            // Drop old columns, add new ones
            $table->dropColumn(['theme', 'retail_price']);
        });
    }

    public function down(): void
    {
        Schema::table('sets', function (Blueprint $table) {
            $table->string('theme')->nullable();
            $table->decimal('retail_price', 10, 2)->nullable();
            $table->dropForeign(['theme_id']);
            $table->dropIndex(['theme_id']);
            $table->dropColumn('theme_id');
        });

        Schema::table('sets', function (Blueprint $table) {
            $table->renameColumn('set_num', 'set_number');
            $table->renameColumn('num_parts', 'pieces');
            $table->renameColumn('img_url', 'image_url');
        });
    }
};
