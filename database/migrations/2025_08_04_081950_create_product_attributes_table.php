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
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_template_id');
            $table->string('name'); // отображаемое название
            $table->string('variable'); // переменная для формулы (только английские буквы)
            $table->enum('type', ['number', 'text', 'select'])->default('number');
            $table->text('options')->nullable(); // для select типа
            $table->string('unit')->nullable(); // единица измерения
            $table->boolean('is_required')->default(false);
            $table->boolean('is_in_formula')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_template_id')->references('id')->on('product_templates')->onDelete('cascade');
            $table->index(['product_template_id', 'sort_order']);
            $table->unique(['product_template_id', 'variable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
