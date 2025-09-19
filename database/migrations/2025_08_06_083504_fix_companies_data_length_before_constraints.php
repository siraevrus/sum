<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Обрезаем данные в полях companies до нужной длины
        $companies = DB::table('companies')->get();

        foreach ($companies as $company) {
            $updates = [];

            if (strlen($company->name) > 60) {
                $updates['name'] = substr($company->name, 0, 60);
            }
            if (strlen($company->general_director) > 60) {
                $updates['general_director'] = substr($company->general_director, 0, 60);
            }
            if (strlen($company->inn) > 10) {
                $updates['inn'] = substr($company->inn, 0, 10);
            }
            if (strlen($company->kpp) > 9) {
                $updates['kpp'] = substr($company->kpp, 0, 9);
            }
            if (strlen($company->ogrn) > 13) {
                $updates['ogrn'] = substr($company->ogrn, 0, 13);
            }
            if (strlen($company->account_number) > 20) {
                $updates['account_number'] = substr($company->account_number, 0, 20);
            }
            if (strlen($company->correspondent_account) > 20) {
                $updates['correspondent_account'] = substr($company->correspondent_account, 0, 20);
            }
            if (strlen($company->bik) > 9) {
                $updates['bik'] = substr($company->bik, 0, 9);
            }

            if (! empty($updates)) {
                DB::table('companies')->where('id', $company->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // В down() ничего не делаем, так как это подготовительная миграция
    }
};
