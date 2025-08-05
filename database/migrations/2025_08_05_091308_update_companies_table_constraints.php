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
        Schema::table('companies', function (Blueprint $table) {
            // Обновляем ограничения для названия и ФИО
            $table->string('name', 60)->change();
            $table->string('general_director', 60)->change();
            
            // Обновляем ограничения для реквизитов
            $table->string('inn', 10)->change();
            $table->string('kpp', 9)->change();
            $table->string('ogrn', 13)->change();
            
            // Обновляем ограничения для банковских реквизитов
            $table->string('account_number', 20)->change();
            $table->string('correspondent_account', 20)->change();
            $table->string('bik', 9)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Возвращаем старые ограничения
            $table->string('name', 100)->change();
            $table->string('general_director', 255)->change();
            
            $table->string('inn', 255)->change();
            $table->string('kpp', 255)->change();
            $table->string('ogrn', 255)->change();
            
            $table->string('account_number', 255)->change();
            $table->string('correspondent_account', 255)->change();
            $table->string('bik', 255)->change();
        });
    }
};
