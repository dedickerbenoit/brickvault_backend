<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('set_id')->constrained()->cascadeOnDelete();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->enum('condition', ['new', 'opened', 'built'])->default('new');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'set_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sets');
    }
};
