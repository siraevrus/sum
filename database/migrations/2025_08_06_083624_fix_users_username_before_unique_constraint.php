<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Получаем всех пользователей с пустыми username
        $users = DB::table('users')->whereNull('username')->orWhere('username', '')->get();
        
        foreach ($users as $user) {
            // Генерируем уникальный username на основе email или имени
            $baseUsername = '';
            
            if (!empty($user->email)) {
                $baseUsername = explode('@', $user->email)[0];
            } elseif (!empty($user->name)) {
                $baseUsername = Str::slug($user->name);
            } else {
                $baseUsername = 'user';
            }
            
            // Проверяем, что username уникален
            $username = $baseUsername;
            $counter = 1;
            
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $baseUsername . '_' . $counter;
                $counter++;
            }
            
            // Обновляем пользователя
            DB::table('users')->where('id', $user->id)->update([
                'username' => $username
            ]);
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
