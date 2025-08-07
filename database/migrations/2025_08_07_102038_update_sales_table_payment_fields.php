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
        Schema::table('sales', function (Blueprint $table) {
            // Удаляем старое поле payment_method
            $table->dropColumn('payment_method');
            
            // Добавляем новые поля для разделения оплаты
            $table->decimal('cash_amount', 10, 2)->default(0.00)->after('total_price');
            $table->decimal('nocash_amount', 10, 2)->default(0.00)->after('cash_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Восстанавливаем старое поле payment_method
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'other'])->default('cash')->after('exchange_rate');
            
            // Удаляем новые поля
            $table->dropColumn(['cash_amount', 'nocash_amount']);
        });
    }
};
