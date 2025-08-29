<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use App\Models\Request;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем администратора
        $admin = User::where('username', 'admin')->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Администратор',
                'username' => 'admin',
                'email' => 'admin@sklad.ru',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
                'first_name' => 'Администратор',
                'last_name' => 'Системы',
                'is_blocked' => false,
            ]);
        }

        // Создаем тестовые компании
        $company1 = Company::create([
            'name' => 'ООО "Тестовая компания 1"',
            'legal_address' => 'г. Москва, ул. Тестовая, д. 1',
            'postal_address' => 'г. Москва, ул. Тестовая, д. 1',
            'phone_fax' => '+7 (495) 123-45-67',
            'general_director' => 'Иванов Иван Иванович',
            'email' => 'info@test1.ru',
            'inn' => '1234567890',
            'kpp' => '123456789',
            'ogrn' => '1234567890123',
            'bank' => 'ПАО Сбербанк',
            'account_number' => '40702810123456789012',
            'correspondent_account' => '30101810400000000225',
            'bik' => '044525225',
        ]);

        $company2 = Company::create([
            'name' => 'ООО "Тестовая компания 2"',
            'legal_address' => 'г. Санкт-Петербург, ул. Тестовая, д. 2',
            'postal_address' => 'г. Санкт-Петербург, ул. Тестовая, д. 2',
            'phone_fax' => '+7 (812) 987-65-43',
            'general_director' => 'Петров Петр Петрович',
            'email' => 'info@test2.ru',
            'inn' => '0987654321',
            'kpp' => '098765432',
            'ogrn' => '0987654321098',
            'bank' => 'ПАО ВТБ',
            'account_number' => '40702810987654321098',
            'correspondent_account' => '30101810700000000187',
            'bik' => '044525187',
        ]);

        // Создаем склады
        $warehouse1 = Warehouse::create([
            'name' => 'Склад №1',
            'address' => 'г. Москва, ул. Складская, д. 10',
            'company_id' => $company1->id,
            'is_active' => true,
        ]);

        $warehouse2 = Warehouse::create([
            'name' => 'Склад №2',
            'address' => 'г. Москва, ул. Складская, д. 20',
            'company_id' => $company1->id,
            'is_active' => true,
        ]);

        $warehouse3 = Warehouse::create([
            'name' => 'Склад №3',
            'address' => 'г. Санкт-Петербург, ул. Складская, д. 30',
            'company_id' => $company2->id,
            'is_active' => true,
        ]);

        // Создаем тестовых сотрудников
        $operator = User::where('username', 'operator')->first();
        if (! $operator) {
            $operator = User::create([
                'name' => 'Оператор ПК',
                'username' => 'operator',
                'email' => 'operator@sklad.ru',
                'password' => Hash::make('password'),
                'role' => UserRole::OPERATOR,
                'first_name' => 'Оператор',
                'last_name' => 'ПК',
                'middle_name' => 'Тестовый',
                'phone' => '+7 (495) 111-11-11',
                'company_id' => $company1->id,
                'warehouse_id' => $warehouse1->id,
                'is_blocked' => false,
            ]);
        }

        $worker = User::where('username', 'worker')->first();
        if (! $worker) {
            $worker = User::create([
                'name' => 'Работник склада',
                'username' => 'worker',
                'email' => 'worker@sklad.ru',
                'password' => Hash::make('password'),
                'role' => UserRole::WAREHOUSE_WORKER,
                'first_name' => 'Работник',
                'last_name' => 'Склада',
                'middle_name' => 'Тестовый',
                'phone' => '+7 (495) 222-22-22',
                'company_id' => $company1->id,
                'warehouse_id' => $warehouse1->id,
                'is_blocked' => false,
            ]);
        }

        $manager = User::where('username', 'manager')->first();
        if (! $manager) {
            $manager = User::create([
                'name' => 'Менеджер по продажам',
                'username' => 'manager',
                'email' => 'manager@sklad.ru',
                'password' => Hash::make('password'),
                'role' => UserRole::SALES_MANAGER,
                'first_name' => 'Менеджер',
                'last_name' => 'По продажам',
                'middle_name' => 'Тестовый',
                'phone' => '+7 (495) 333-33-33',
                'company_id' => $company2->id,
                'warehouse_id' => $warehouse3->id,
                'is_blocked' => false,
            ]);
        }

        // Создаем тестовые шаблоны товаров
        $template1 = ProductTemplate::create([
            'name' => 'Доска обрезная',
            'description' => 'Обрезная доска из хвойных пород дерева',
            'formula' => 'length * width * height',
            'unit' => 'м³',
            'is_active' => true,
        ]);

        // Характеристики для доски
        ProductAttribute::create([
            'product_template_id' => $template1->id,
            'name' => 'Длина',
            'variable' => 'length',
            'type' => 'number',
            'unit' => 'метр',
            'is_required' => true,
            'is_in_formula' => true,
            'sort_order' => 1,
        ]);

        ProductAttribute::create([
            'product_template_id' => $template1->id,
            'name' => 'Ширина',
            'variable' => 'width',
            'type' => 'number',
            'unit' => 'см',
            'is_required' => true,
            'is_in_formula' => true,
            'sort_order' => 2,
        ]);

        ProductAttribute::create([
            'product_template_id' => $template1->id,
            'name' => 'Толщина',
            'variable' => 'height',
            'type' => 'number',
            'unit' => 'мм',
            'is_required' => true,
            'is_in_formula' => true,
            'sort_order' => 3,
        ]);

        ProductAttribute::create([
            'product_template_id' => $template1->id,
            'name' => 'Сорт',
            'variable' => 'grade',
            'type' => 'select',
            'options' => ['A', 'B', 'C'],
            'is_required' => true,
            'is_in_formula' => false,
            'sort_order' => 4,
        ]);

        $template2 = ProductTemplate::create([
            'name' => 'Брус',
            'description' => 'Брус строительный',
            'formula' => 'length * width * width',
            'unit' => 'м³',
            'is_active' => true,
        ]);

        // Характеристики для бруса
        ProductAttribute::create([
            'product_template_id' => $template2->id,
            'name' => 'Длина',
            'variable' => 'length',
            'type' => 'number',
            'unit' => 'метр',
            'is_required' => true,
            'is_in_formula' => true,
            'sort_order' => 1,
        ]);

        ProductAttribute::create([
            'product_template_id' => $template2->id,
            'name' => 'Сечение',
            'variable' => 'width',
            'type' => 'number',
            'unit' => 'см',
            'is_required' => true,
            'is_in_formula' => true,
            'sort_order' => 2,
        ]);

        ProductAttribute::create([
            'product_template_id' => $template2->id,
            'name' => 'Порода дерева',
            'variable' => 'wood_type',
            'type' => 'select',
            'options' => ['Сосна', 'Ель', 'Лиственница'],
            'is_required' => true,
            'is_in_formula' => false,
            'sort_order' => 3,
        ]);

        $template3 = ProductTemplate::create([
            'name' => 'Цилиндр',
            'description' => 'Цилиндрические изделия',
            'formula' => '3.14159 * radius * radius * height',
            'unit' => 'м³',
            'is_active' => true,
        ]);

        // Характеристики для цилиндра
        ProductAttribute::create([
            'product_template_id' => $template3->id,
            'name' => 'Радиус',
            'variable' => 'radius',
            'type' => 'number',
            'unit' => 'метр',
            'is_required' => true,
            'is_in_formula' => true,
            'sort_order' => 1,
        ]);

        ProductAttribute::create([
            'product_template_id' => $template3->id,
            'name' => 'Высота',
            'variable' => 'height',
            'type' => 'number',
            'unit' => 'метр',
            'is_required' => true,
            'is_in_formula' => true,
            'sort_order' => 2,
        ]);

        ProductAttribute::create([
            'product_template_id' => $template3->id,
            'name' => 'Материал',
            'variable' => 'material',
            'type' => 'select',
            'options' => ['Металл', 'Пластик', 'Дерево'],
            'is_required' => true,
            'is_in_formula' => false,
            'sort_order' => 3,
        ]);

        // Создаем тестовые товары
        $admin = User::where('email', 'admin@sklad.ru')->first();

        // Товары для доски обрезной
        Product::create([
            'product_template_id' => $template1->id,
            'warehouse_id' => $warehouse1->id,
            'created_by' => $admin->id,
            'name' => 'Доска обрезная 6м',
            'description' => 'Обрезная доска 6 метров длиной',
            'attributes' => [
                'length' => 6.0,
                'width' => 15.0,
                'height' => 25.0,
                'grade' => 'A',
            ],
            'calculated_volume' => 0.0225, // 6 * 0.15 * 0.025
            'quantity' => 50,
            'producer' => 'ООО "Лесопилка"',
            'arrival_date' => now()->subDays(30),
            'is_active' => true,
        ]);

        Product::create([
            'product_template_id' => $template1->id,
            'warehouse_id' => $warehouse1->id,
            'created_by' => $admin->id,
            'name' => 'Доска обрезная 4м',
            'description' => 'Обрезная доска 4 метра длиной',
            'attributes' => [
                'length' => 4.0,
                'width' => 20.0,
                'height' => 30.0,
                'grade' => 'B',
            ],
            'calculated_volume' => 0.024, // 4 * 0.20 * 0.030
            'quantity' => 30,
            'producer' => 'ООО "Лесопилка"',
            'arrival_date' => now()->subDays(15),
            'is_active' => true,
        ]);

        // Товары для бруса
        Product::create([
            'product_template_id' => $template2->id,
            'warehouse_id' => $warehouse2->id,
            'created_by' => $admin->id,
            'name' => 'Брус 6м 150x150',
            'description' => 'Брус строительный 6 метров',
            'attributes' => [
                'length' => 6.0,
                'width' => 15.0,
                'wood_type' => 'Сосна',
            ],
            'calculated_volume' => 0.135, // 6 * 0.15 * 0.15
            'quantity' => 25,
            'producer' => 'ООО "Лесопилка"',
            'arrival_date' => now()->subDays(20),
            'is_active' => true,
        ]);

        Product::create([
            'product_template_id' => $template2->id,
            'warehouse_id' => $warehouse2->id,
            'created_by' => $admin->id,
            'name' => 'Брус 4м 100x100',
            'description' => 'Брус строительный 4 метра',
            'attributes' => [
                'length' => 4.0,
                'width' => 10.0,
                'wood_type' => 'Ель',
            ],
            'calculated_volume' => 0.04, // 4 * 0.10 * 0.10
            'quantity' => 40,
            'producer' => 'ООО "Лесопилка"',
            'arrival_date' => now()->subDays(10),
            'is_active' => true,
        ]);

        // Товары для цилиндра
        Product::create([
            'product_template_id' => $template3->id,
            'warehouse_id' => $warehouse3->id,
            'created_by' => $admin->id,
            'name' => 'Цилиндр металлический',
            'description' => 'Металлический цилиндр',
            'attributes' => [
                'radius' => 0.5,
                'height' => 2.0,
                'material' => 'Металл',
            ],
            'calculated_volume' => 1.5708, // 3.14159 * 0.5 * 0.5 * 2
            'quantity' => 10,
            'producer' => 'ООО "МеталлПром"',
            'arrival_date' => now()->subDays(5),
            'is_active' => true,
        ]);

        Product::create([
            'product_template_id' => $template3->id,
            'warehouse_id' => $warehouse3->id,
            'created_by' => $admin->id,
            'name' => 'Цилиндр пластиковый',
            'description' => 'Пластиковый цилиндр',
            'attributes' => [
                'radius' => 0.3,
                'height' => 1.5,
                'material' => 'Пластик',
            ],
            'calculated_volume' => 0.4241, // 3.14159 * 0.3 * 0.3 * 1.5
            'quantity' => 15,
            'producer' => 'ООО "ПластПром"',
            'arrival_date' => now()->subDays(3),
            'is_active' => true,
        ]);

        // Создаем тестовые товары в пути
        // Товары в пути для доски обрезной
        ProductInTransit::create([
            'product_template_id' => $template1->id,
            'warehouse_id' => $warehouse1->id,
            'created_by' => $admin->id,
            'name' => 'Доска обрезная 6м (в пути)',
            'description' => 'Обрезная доска 6 метров в доставке',
            'attributes' => [
                'length' => 6.0,
                'width' => 15.0,
                'height' => 25.0,
                'grade' => 'A',
            ],
            'calculated_volume' => 0.0225,
            'quantity' => 20,
            'producer' => 'ООО "Лесопилка"',
            'transport_number' => 'TR001',
            'tracking_number' => 'TN12345678',
            'expected_arrival_date' => now()->addDays(5),
            'status' => ProductInTransit::STATUS_IN_TRANSIT,
            'notes' => 'Товар в пути, ожидается через 5 дней',
            'is_active' => true,
        ]);

        ProductInTransit::create([
            'product_template_id' => $template1->id,
            'warehouse_id' => $warehouse2->id,
            'created_by' => $admin->id,
            'name' => 'Доска обрезная 4м (заказана)',
            'description' => 'Обрезная доска 4 метра заказана',
            'attributes' => [
                'length' => 4.0,
                'width' => 20.0,
                'height' => 30.0,
                'grade' => 'B',
            ],
            'calculated_volume' => 0.024,
            'quantity' => 15,
            'producer' => 'ООО "Лесопилка"',
            'transport_number' => null,
            'tracking_number' => null,
            'expected_arrival_date' => now()->addDays(15),
            'status' => ProductInTransit::STATUS_ORDERED,
            'notes' => 'Заказ подтвержден поставщиком',
            'is_active' => true,
        ]);

        // Товары в пути для бруса
        ProductInTransit::create([
            'product_template_id' => $template2->id,
            'warehouse_id' => $warehouse2->id,
            'created_by' => $admin->id,
            'name' => 'Брус 6м 150x150 (прибыл)',
            'description' => 'Брус строительный 6 метров прибыл на склад',
            'attributes' => [
                'length' => 6.0,
                'width' => 15.0,
                'wood_type' => 'Сосна',
            ],
            'calculated_volume' => 0.135,
            'quantity' => 10,
            'producer' => 'ООО "Лесопилка"',
            'transport_number' => 'TR002',
            'tracking_number' => 'TN87654321',
            'expected_arrival_date' => now()->subDays(2),
            'actual_arrival_date' => now()->subDays(1),
            'status' => ProductInTransit::STATUS_ARRIVED,
            'notes' => 'Товар прибыл, ожидает приемки',
            'is_active' => true,
        ]);

        // Товары в пути для цилиндра
        ProductInTransit::create([
            'product_template_id' => $template3->id,
            'warehouse_id' => $warehouse3->id,
            'created_by' => $admin->id,
            'name' => 'Цилиндр металлический (просрочен)',
            'description' => 'Металлический цилиндр с просроченной доставкой',
            'attributes' => [
                'radius' => 0.5,
                'height' => 2.0,
                'material' => 'Металл',
            ],
            'calculated_volume' => 1.5708,
            'quantity' => 5,
            'producer' => 'ООО "МеталлПром"',
            'transport_number' => 'TR003',
            'tracking_number' => 'TN11111111',
            'expected_arrival_date' => now()->subDays(10),
            'status' => ProductInTransit::STATUS_IN_TRANSIT,
            'notes' => 'Доставка задерживается, связь с поставщиком',
            'is_active' => true,
        ]);

        // Создаем тестовые запросы
        $operator = User::where('email', 'operator@sklad.ru')->first();
        $worker = User::where('email', 'worker@sklad.ru')->first();
        $manager = User::where('email', 'manager@sklad.ru')->first();

        // Запросы от оператора ПК
        Request::create([
            'user_id' => $operator->id,
            'warehouse_id' => $warehouse1->id,
            'product_template_id' => $template1->id,
            'title' => 'Запрос на доску обрезную',
            'description' => 'Необходимо 10 досок обрезных 6 метров для строительных работ. Срочно требуется для завершения проекта.',
            'quantity' => 10,
            'priority' => Request::PRIORITY_HIGH,
            'status' => Request::STATUS_PENDING,
            'is_active' => true,
        ]);

        Request::create([
            'user_id' => $operator->id,
            'warehouse_id' => $warehouse1->id,
            'product_template_id' => $template2->id,
            'title' => 'Запрос на брус строительный',
            'description' => 'Требуется брус 150x150 для возведения каркаса. Количество: 5 штук.',
            'quantity' => 5,
            'priority' => Request::PRIORITY_NORMAL,
            'status' => Request::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(2),
            'is_active' => true,
        ]);

        // Запросы от работника склада
        Request::create([
            'user_id' => $worker->id,
            'warehouse_id' => $warehouse2->id,
            'product_template_id' => $template1->id,
            'title' => 'Срочный запрос на доски',
            'description' => 'КРИТИЧНО! Необходимы доски для срочного ремонта. Приоритет максимальный.',
            'quantity' => 15,
            'priority' => Request::PRIORITY_URGENT,
            'status' => Request::STATUS_IN_PROGRESS,
            'approved_by' => $admin->id,
            'processed_by' => $admin->id,
            'approved_at' => now()->subDays(5),
            'processed_at' => now()->subDays(3),
            'is_active' => true,
        ]);

        Request::create([
            'user_id' => $worker->id,
            'warehouse_id' => $warehouse2->id,
            'product_template_id' => $template3->id,
            'title' => 'Запрос на цилиндры',
            'description' => 'Нужны металлические цилиндры для производственных нужд.',
            'quantity' => 3,
            'priority' => Request::PRIORITY_LOW,
            'status' => Request::STATUS_COMPLETED,
            'approved_by' => $admin->id,
            'processed_by' => $admin->id,
            'approved_at' => now()->subDays(10),
            'processed_at' => now()->subDays(8),
            'completed_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        // Запросы от менеджера по продажам
        Request::create([
            'user_id' => $manager->id,
            'warehouse_id' => $warehouse3->id,
            'product_template_id' => $template2->id,
            'title' => 'Запрос на брус для клиента',
            'description' => 'Клиент заказал брус 100x100. Необходимо подготовить к отгрузке.',
            'quantity' => 8,
            'priority' => Request::PRIORITY_HIGH,
            'status' => Request::STATUS_REJECTED,
            'admin_notes' => 'Отклонено: недостаточно остатков на складе',
            'is_active' => true,
        ]);

        Request::create([
            'user_id' => $manager->id,
            'warehouse_id' => $warehouse3->id,
            'product_template_id' => null,
            'title' => 'Запрос на различные материалы',
            'description' => 'Нужны различные строительные материалы для крупного заказа. Уточнить детали по телефону.',
            'quantity' => 1,
            'priority' => Request::PRIORITY_NORMAL,
            'status' => Request::STATUS_PENDING,
            'is_active' => true,
        ]);

        // Создаем тестовые продажи
        $products = Product::where('quantity', '>', 0)->get();

        // Продажа доски обрезной
        if ($products->where('name', 'Доска обрезная 6м')->first()) {
            $product = $products->where('name', 'Доска обрезная 6м')->first();
            Sale::create([
                'product_id' => $product->id,
                'warehouse_id' => $product->warehouse_id,
                'user_id' => $operator->id,
                'sale_number' => Sale::generateSaleNumber(),
                'customer_name' => 'Иванов Иван Иванович',
                'customer_phone' => '+7 (495) 123-45-67',
                'customer_email' => 'ivanov@example.com',
                'customer_address' => 'г. Москва, ул. Примерная, д. 1, кв. 10',
                'quantity' => 5,
                'unit_price' => 2500.00,
                'total_price' => 15000.00,
                'vat_rate' => 20.00,
                'vat_amount' => 2500.00,
                'price_without_vat' => 12500.00,
                'currency' => 'RUB',
                'exchange_rate' => 1.0000,
                'payment_status' => Sale::PAYMENT_STATUS_PAID,
                'delivery_status' => Sale::DELIVERY_STATUS_DELIVERED,
                'notes' => 'Продажа для строительных работ',
                'invoice_number' => 'INV-001234',
                'sale_date' => now()->subDays(5),
                'delivery_date' => now()->subDays(3),
                'is_active' => true,
            ]);
        }

        // Продажа бруса
        if ($products->where('name', 'Брус 6м 150x150')->first()) {
            $product = $products->where('name', 'Брус 6м 150x150')->first();
            Sale::create([
                'product_id' => $product->id,
                'warehouse_id' => $product->warehouse_id,
                'user_id' => $worker->id,
                'sale_number' => Sale::generateSaleNumber(),
                'customer_name' => 'Петров Петр Петрович',
                'customer_phone' => '+7 (495) 987-65-43',
                'customer_email' => 'petrov@example.com',
                'customer_address' => 'г. Москва, ул. Строительная, д. 15, кв. 25',
                'quantity' => 3,
                'unit_price' => 4500.00,
                'total_price' => 16200.00,
                'vat_rate' => 20.00,
                'vat_amount' => 2700.00,
                'price_without_vat' => 13500.00,
                'currency' => 'RUB',
                'exchange_rate' => 1.0000,
                'payment_status' => Sale::PAYMENT_STATUS_PAID,
                'delivery_status' => Sale::DELIVERY_STATUS_IN_PROGRESS,
                'notes' => 'Доставка на строительную площадку',
                'invoice_number' => 'INV-001235',
                'sale_date' => now()->subDays(2),
                'delivery_date' => null,
                'is_active' => true,
            ]);
        }

        // Продажа цилиндра
        if ($products->where('name', 'Цилиндр металлический')->first()) {
            $product = $products->where('name', 'Цилиндр металлический')->first();
            Sale::create([
                'product_id' => $product->id,
                'warehouse_id' => $product->warehouse_id,
                'user_id' => $manager->id,
                'sale_number' => Sale::generateSaleNumber(),
                'customer_name' => 'Сидоров Сидор Сидорович',
                'customer_phone' => '+7 (495) 555-55-55',
                'customer_email' => 'sidorov@example.com',
                'customer_address' => 'г. Москва, ул. Промышленная, д. 100',
                'quantity' => 2,
                'unit_price' => 8000.00,
                'total_price' => 19200.00,
                'vat_rate' => 20.00,
                'vat_amount' => 3200.00,
                'price_without_vat' => 16000.00,
                'currency' => 'RUB',
                'exchange_rate' => 1.0000,
                'payment_status' => Sale::PAYMENT_STATUS_PENDING,
                'delivery_status' => Sale::DELIVERY_STATUS_PENDING,
                'notes' => 'Ожидает оплаты наличными',
                'invoice_number' => 'INV-001236',
                'sale_date' => now(),
                'delivery_date' => null,
                'is_active' => true,
            ]);
        }

        // Отмененная продажа
        if ($products->where('name', 'Доска обрезная 4м')->first()) {
            $product = $products->where('name', 'Доска обрезная 4м')->first();
            Sale::create([
                'product_id' => $product->id,
                'warehouse_id' => $product->warehouse_id,
                'user_id' => $operator->id,
                'sale_number' => Sale::generateSaleNumber(),
                'customer_name' => 'Отмененный клиент',
                'customer_phone' => '+7 (495) 000-00-00',
                'customer_email' => 'cancelled@example.com',
                'customer_address' => 'г. Москва, ул. Отмененная, д. 0',
                'quantity' => 10,
                'unit_price' => 2000.00,
                'total_price' => 24000.00,
                'vat_rate' => 20.00,
                'vat_amount' => 4000.00,
                'price_without_vat' => 20000.00,
                'currency' => 'RUB',
                'exchange_rate' => 1.0000,
                'payment_status' => Sale::PAYMENT_STATUS_CANCELLED,
                'delivery_status' => Sale::DELIVERY_STATUS_CANCELLED,
                'notes' => 'Продажа отменена по просьбе клиента',
                'invoice_number' => 'INV-001237',
                'sale_date' => now()->subDays(10),
                'delivery_date' => null,
                'is_active' => true,
            ]);
        }
    }
}
