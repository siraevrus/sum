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
        Schema::table('product_in_transit', function (Blueprint $table) {
            $table->unsignedBigInteger('producer_id')->nullable()->after('producer');
            $table->foreign('producer_id')->references('id')->on('producers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_in_transit', function (Blueprint $table) {
            $table->dropForeign(['producer_id']);
            $table->dropColumn('producer_id');
        });
    }
};
