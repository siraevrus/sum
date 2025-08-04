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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_template_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('created_by'); // кто создал товар
            $table->string('name'); // наименование товара
            $table->text('description')->nullable();
            $table->json('attributes'); // характеристики товара в JSON
            $table->decimal('calculated_volume', 10, 4)->nullable(); // рассчитанный объем
            $table->integer('quantity')->default(1); // количество
            $table->string('transport_number')->nullable(); // номер транспортного средства
            $table->string('producer')->nullable(); // производитель
            $table->date('arrival_date'); // дата поступления
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('product_template_id')->references('id')->on('product_templates')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['warehouse_id', 'is_active']);
            $table->index(['product_template_id', 'is_active']);
            $table->index('producer');
            $table->index('arrival_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
