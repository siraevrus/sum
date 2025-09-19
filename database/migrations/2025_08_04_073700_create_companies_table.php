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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('legal_address')->nullable();
            $table->text('postal_address')->nullable();
            $table->string('phone_fax')->nullable();
            $table->string('general_director')->nullable();
            $table->string('email')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->string('ogrn')->nullable();
            $table->string('bank')->nullable();
            $table->string('account_number')->nullable();
            $table->string('correspondent_account')->nullable();
            $table->string('bik')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
