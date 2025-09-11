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
        Schema::table('products', function (Blueprint $table) {
            // Drop indexes first if they exist
            $table->dropIndex(['producer']); // Drop producer index
            $table->dropColumn(['producer', 'tracking_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('producer')->nullable();
            $table->string('tracking_number')->nullable();
            $table->index('producer'); // Recreate producer index
        });
    }
};
