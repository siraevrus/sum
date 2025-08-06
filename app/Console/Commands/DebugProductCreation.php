<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use App\Models\Company;
use App\Models\User;
use App\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugProductCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:product-creation {--user-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug product creation issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Debug Product Creation ===');

        // Проверяем подключение к базе данных
        try {
            $this->info('✓ Database connection: OK');
        } catch (\Exception $e) {
            $this->error('✗ Database connection failed: ' . $e->getMessage());
            return 1;
        }

        // Проверяем структуру таблиц
        $this->info('=== Database Schema ===');
        $this->checkTableSchema('products');
        $this->checkTableSchema('product_templates');
        $this->checkTableSchema('warehouses');
        $this->checkTableSchema('companies');
        $this->checkTableSchema('users');

        // Проверяем данные
        $this->info('=== Data Check ===');
        $this->checkData();

        // Проверяем пользователя
        $userId = $this->option('user-id');
        if ($userId) {
            $this->info('=== User Check ===');
            $this->checkUser($userId);
        }

        // Пытаемся создать тестовый товар
        $this->info('=== Test Product Creation ===');
        $this->testProductCreation();

        $this->info('=== Debug Complete ===');
        return 0;
    }

    private function checkTableSchema(string $table): void
    {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            $this->info("✓ Table {$table}: exists");
            
            // Проверяем важные колонки
            $columns = ['id'];
            if ($table === 'products') {
                $columns = ['id', 'product_template_id', 'warehouse_id', 'name', 'quantity', 'attributes', 'created_by'];
            } elseif ($table === 'users') {
                $columns = ['id', 'name', 'email', 'role', 'company_id'];
            }
            
            foreach ($columns as $column) {
                if (DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    $this->info("  ✓ Column {$table}.{$column}: exists");
                } else {
                    $this->error("  ✗ Column {$table}.{$column}: missing");
                }
            }
        } else {
            $this->error("✗ Table {$table}: missing");
        }
    }

    private function checkData(): void
    {
        $counts = [
            'companies' => Company::count(),
            'warehouses' => Warehouse::count(),
            'product_templates' => ProductTemplate::count(),
            'users' => User::count(),
            'products' => Product::count(),
        ];

        foreach ($counts as $table => $count) {
            $this->info("✓ {$table}: {$count} records");
        }
    }

    private function checkUser(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            $this->error("✗ User with ID {$userId} not found");
            return;
        }

        $this->info("✓ User found: {$user->name} ({$user->email})");
        $this->info("  Role: {$user->role->value}");
        $this->info("  Company ID: {$user->company_id}");
        
        // Проверяем права доступа
        $canViewProducts = in_array($user->role->value, ['admin', 'operator']);
        $this->info("  Can view products: " . ($canViewProducts ? 'Yes' : 'No'));
    }

    private function testProductCreation(): void
    {
        try {
            // Находим необходимые данные
            $template = ProductTemplate::first();
            $warehouse = Warehouse::first();
            $user = User::first();

            if (!$template || !$warehouse || !$user) {
                $this->error('✗ Missing required data for test creation');
                return;
            }

            $this->info("✓ Found template: {$template->name}");
            $this->info("✓ Found warehouse: {$warehouse->name}");
            $this->info("✓ Found user: {$user->name}");

            // Пытаемся создать товар
            $productData = [
                'product_template_id' => $template->id,
                'warehouse_id' => $warehouse->id,
                'name' => 'Debug Test Product ' . time(),
                'quantity' => 1,
                'arrival_date' => now(),
                'is_active' => true,
                'attributes' => [],
                'created_by' => $user->id,
            ];

            $product = Product::create($productData);
            
            $this->info("✓ Test product created successfully!");
            $this->info("  ID: {$product->id}");
            $this->info("  Name: {$product->name}");
            
            // Удаляем тестовый товар
            $product->delete();
            $this->info("✓ Test product deleted");

        } catch (\Exception $e) {
            $this->error('✗ Product creation failed: ' . $e->getMessage());
        }
    }
}
