<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sets', function (Blueprint $table) {
            $table->id();
            $table->string('set_number')->unique();
            $table->string('name');
            $table->string('theme')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('pieces')->nullable();
            $table->decimal('retail_price', 10, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sets');
    }
};
