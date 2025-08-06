<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Обрезаем данные в полях companies до нужной длины
        DB::table('companies')->update([
            'name' => DB::raw('LEFT(name, 60)'),
            'general_director' => DB::raw('LEFT(general_director, 60)'),
            'inn' => DB::raw('LEFT(inn, 10)'),
            'kpp' => DB::raw('LEFT(kpp, 9)'),
            'ogrn' => DB::raw('LEFT(ogrn, 13)'),
            'account_number' => DB::raw('LEFT(account_number, 20)'),
            'correspondent_account' => DB::raw('LEFT(correspondent_account, 20)'),
            'bik' => DB::raw('LEFT(bik, 9)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // В down() ничего не делаем, так как это подготовительная миграция
    }
};
