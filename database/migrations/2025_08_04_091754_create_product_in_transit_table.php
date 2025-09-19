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
        Schema::create('product_in_transit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_template_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('created_by'); // кто создал запись
            $table->string('name'); // наименование товара
            $table->text('description')->nullable();
            $table->json('attributes'); // характеристики товара в JSON
            $table->decimal('calculated_volume', 10, 4)->nullable(); // рассчитанный объем
            $table->integer('quantity'); // количество
            $table->string('transport_number')->nullable(); // номер транспортного средства
            $table->string('producer')->nullable(); // производитель
            $table->string('supplier')->nullable(); // поставщик
            $table->string('tracking_number')->nullable(); // номер отслеживания
            $table->date('expected_arrival_date'); // ожидаемая дата прибытия
            $table->date('actual_arrival_date')->nullable(); // фактическая дата прибытия
            $table->enum('status', ['ordered', 'in_transit', 'arrived', 'received', 'cancelled'])->default('ordered');
            $table->text('notes')->nullable(); // заметки
            $table->string('document_path')->nullable(); // путь к документам
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('product_template_id')->references('id')->on('product_templates')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['warehouse_id', 'status']);
            $table->index(['product_template_id', 'status']);
            $table->index('producer');
            $table->index('supplier');
            $table->index('expected_arrival_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_in_transit');
    }
};
