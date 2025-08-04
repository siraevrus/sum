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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // кто создал запрос
            $table->unsignedBigInteger('warehouse_id'); // склад, с которого запрашивается товар
            $table->unsignedBigInteger('product_template_id')->nullable(); // шаблон товара (если указан)
            $table->string('title'); // заголовок запроса
            $table->text('description'); // описание запроса
            $table->integer('quantity')->default(1); // количество
            $table->string('priority')->default('normal'); // приоритет (low, normal, high, urgent)
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('admin_notes')->nullable(); // заметки администратора
            $table->unsignedBigInteger('approved_by')->nullable(); // кто одобрил
            $table->unsignedBigInteger('processed_by')->nullable(); // кто обработал
            $table->timestamp('approved_at')->nullable(); // когда одобрено
            $table->timestamp('processed_at')->nullable(); // когда обработано
            $table->timestamp('completed_at')->nullable(); // когда завершено
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_template_id')->references('id')->on('product_templates')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['warehouse_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['product_template_id', 'status']);
            $table->index('priority');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
