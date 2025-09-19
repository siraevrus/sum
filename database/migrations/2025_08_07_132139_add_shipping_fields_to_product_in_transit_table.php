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
        // Для SQLite необходимо сначала удалить индекс по столбцу, затем удалять столбец
        if (Schema::hasColumn('product_in_transit', 'supplier')) {
            // Пытаемся удалить индекс по имени и по определению на случай различий в драйверах
            try {
                Schema::table('product_in_transit', function (Blueprint $table) {
                    $table->dropIndex('product_in_transit_supplier_index');
                });
            } catch (\Throwable $e) {
                // игнорируем, если индекс с таким именем не существует
            }

            try {
                Schema::table('product_in_transit', function (Blueprint $table) {
                    $table->dropIndex(['supplier']);
                });
            } catch (\Throwable $e) {
                // игнорируем, если индекс по столбцу уже отсутствует
            }
        }

        Schema::table('product_in_transit', function (Blueprint $table) {
            if (! Schema::hasColumn('product_in_transit', 'shipping_location')) {
                $table->string('shipping_location')->nullable();
            }
            if (! Schema::hasColumn('product_in_transit', 'shipping_date')) {
                $table->date('shipping_date')->nullable();
            }
            if (Schema::hasColumn('product_in_transit', 'supplier')) {
                $table->dropColumn('supplier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_in_transit', function (Blueprint $table) {
            if (! Schema::hasColumn('product_in_transit', 'supplier')) {
                $table->string('supplier')->nullable();
            }
            if (Schema::hasColumn('product_in_transit', 'shipping_location')) {
                $table->dropColumn('shipping_location');
            }
            if (Schema::hasColumn('product_in_transit', 'shipping_date')) {
                $table->dropColumn('shipping_date');
            }
        });

        // Восстановим индекс для совместимости при откате
        Schema::table('product_in_transit', function (Blueprint $table) {
            if (Schema::hasColumn('product_in_transit', 'supplier')) {
                try {
                    $table->index('supplier');
                } catch (\Throwable $e) {
                    // игнорируем, если индекс уже существует
                }
            }
        });
    }
};
