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
            $table->string('shipping_location')->nullable()->after('supplier');
            $table->date('shipping_date')->nullable()->after('shipping_location');
            $table->dropColumn('supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_in_transit', function (Blueprint $table) {
            $table->string('supplier')->nullable()->after('producer');
            $table->dropColumn(['shipping_location', 'shipping_date']);
        });
    }
};
