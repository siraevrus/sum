<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DatabaseConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Отключаем ONLY_FULL_GROUP_BY для MySQL только если БД доступна
        if (config('database.default') === 'mysql') {
            try {
                DB::statement("SET sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
            } catch (\Exception $e) {
                // Игнорируем ошибки подключения к БД при загрузке приложения
                // Это позволяет выполнять команды типа key:generate без БД
            }
        }
    }
}
