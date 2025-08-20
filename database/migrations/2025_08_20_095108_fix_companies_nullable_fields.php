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
            // Исправляем поля, которые должны быть nullable
            $table->string('general_director', 255)->nullable()->change();
            $table->string('inn', 10)->nullable()->change();
            $table->string('kpp', 9)->nullable()->change();
            $table->string('ogrn', 13)->nullable()->change();
            $table->string('account_number', 20)->nullable()->change();
            $table->string('correspondent_account', 20)->nullable()->change();
            $table->string('bik', 9)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Возвращаем поля как NOT NULL
            $table->string('general_director', 255)->nullable(false)->change();
            $table->string('inn', 10)->nullable(false)->change();
            $table->string('kpp', 9)->nullable(false)->change();
            $table->string('ogrn', 13)->nullable(false)->change();
            $table->string('account_number', 20)->nullable(false)->change();
            $table->string('correspondent_account', 20)->nullable(false)->change();
            $table->string('bik', 9)->nullable(false)->change();
        });
    }
};
