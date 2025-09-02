<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_in_transit_id')->constrained('product_in_transit');
            $table->foreignId('user_id')->constrained('users');
            $table->string('reason');
            $table->integer('old_quantity')->nullable();
            $table->integer('new_quantity')->nullable();
            $table->string('old_color')->nullable();
            $table->string('new_color')->nullable();
            $table->string('old_size')->nullable();
            $table->string('new_size')->nullable();
            $table->decimal('old_weight', 10, 4)->nullable();
            $table->decimal('new_weight', 10, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancies');
    }
};
