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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // товар, который продается
            $table->unsignedBigInteger('warehouse_id'); // склад, с которого продается
            $table->unsignedBigInteger('user_id'); // кто оформил продажу
            $table->string('sale_number'); // номер продажи
            $table->string('customer_name')->nullable(); // имя клиента
            $table->string('customer_phone')->nullable(); // телефон клиента
            $table->string('customer_email')->nullable(); // email клиента
            $table->text('customer_address')->nullable(); // адрес клиента
            $table->integer('quantity'); // количество проданного товара
            $table->decimal('unit_price', 10, 2); // цена за единицу
            $table->decimal('total_price', 10, 2); // общая стоимость
            $table->decimal('vat_rate', 5, 2)->default(20.00); // ставка НДС (%)
            $table->decimal('vat_amount', 10, 2); // сумма НДС
            $table->decimal('price_without_vat', 10, 2); // цена без НДС
            $table->string('currency', 3)->default('RUB'); // валюта
            $table->decimal('exchange_rate', 10, 4)->default(1.0000); // курс валюты
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'other'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'partially_paid', 'cancelled'])->default('pending');
            $table->text('notes')->nullable(); // заметки
            $table->string('invoice_number')->nullable(); // номер счета
            $table->date('sale_date'); // дата продажи
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['warehouse_id', 'sale_date']);
            $table->index(['user_id', 'sale_date']);
            $table->index(['product_id', 'sale_date']);
            $table->index('sale_number');
            $table->index('payment_status');
            $table->index('sale_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
