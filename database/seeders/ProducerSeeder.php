<?php

namespace Database\Seeders;

use App\Models\Producer;
use Illuminate\Database\Seeder;

class ProducerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем известных производителей
        $producers = [
            'Apple Inc.',
            'Samsung Electronics',
            'Huawei Technologies',
            'Xiaomi Corporation',
            'LG Electronics',
            'Sony Corporation',
            'Microsoft Corporation',
            'Google LLC',
            'Amazon.com Inc.',
            'Tesla Inc.',
        ];

        foreach ($producers as $producerName) {
            Producer::firstOrCreate([
                'name' => $producerName,
            ]);
        }

        // Создаем еще 5 случайных производителей
        Producer::factory(5)->create();
    }
}
