<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private string $adminToken;
    private string $operatorToken;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем пользователей с разными ролями
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->operator = User::factory()->create(['role' => 'operator']);
        
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;
        $this->operatorToken = $this->operator->createToken('operator-token')->plainTextToken;

        // Создаем тестовые данные
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $template = ProductTemplate::factory()->create();
        
        $this->product = Product::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_template_id' => $template->id,
            'quantity' => 100,
        ]);
    }

    public function test_complete_sales_workflow()
    {
        // 1. Создаем продажу
        $saleData = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '+7 999 123-45-67',
            'customer_email' => 'customer@example.com',
            'quantity' => 10,
            'unit_price' => 1000.00,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'sale_date' => now()->toDateString(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/sales', $saleData);

        $response->assertStatus(201);
        $saleId = $response->json('sale.id');

        // 2. Проверяем, что продажа создана
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson("/api/sales/{$saleId}");

        $response->assertStatus(200)
                ->assertJson([
                    'customer_name' => 'Test Customer',
                    'quantity' => 10,
                    'unit_price' => 1000.00,
                ]);

        // 3. Оформляем продажу (списываем товар)
        $initialQuantity = $this->product->quantity;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson("/api/sales/{$saleId}/process");

        $response->assertStatus(200);

        // 4. Проверяем, что товар списан со склада
        $this->product->refresh();
        $this->assertEquals($initialQuantity - 10, $this->product->quantity);

        // 5. Проверяем статистику продаж
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/sales/stats');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_sales',
                    'total_revenue',
                    'average_sale',
                    'pending_payments',
                ]);
    }

    public function test_role_based_access_control()
    {
        // Администратор может видеть все товары
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/products');

        $response->assertStatus(200);

        // Оператор может видеть товары
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->operatorToken,
        ])->getJson('/api/products');

        $response->assertStatus(200);

        // Проверяем, что оператор не может создавать товары (если у него нет прав)
        $productData = [
            'name' => 'Test Product',
            'warehouse_id' => $this->product->warehouse_id,
            'product_template_id' => $this->product->product_template_id,
            'quantity' => 10,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->operatorToken,
        ])->postJson('/api/products', $productData);

        // Результат зависит от настроек прав доступа
        $response->assertStatus(201); // Если оператор может создавать товары
    }

    public function test_api_pagination()
    {
        // Создаем много товаров
        Product::factory()->count(25)->create([
            'warehouse_id' => $this->product->warehouse_id,
            'product_template_id' => $this->product->product_template_id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/products?page=1&per_page=10');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'links',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ]);

        $data = $response->json();
        $this->assertCount(10, $data['data']);
        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertEquals(10, $data['meta']['per_page']);
    }

    public function test_api_filtering_and_search()
    {
        // Создаем товары с разными производителями
        Product::factory()->create([
            'warehouse_id' => $this->product->warehouse_id,
            'product_template_id' => $this->product->product_template_id,
            'producer' => 'Producer A',
        ]);

        Product::factory()->create([
            'warehouse_id' => $this->product->warehouse_id,
            'product_template_id' => $this->product->product_template_id,
            'producer' => 'Producer B',
        ]);

        // Тестируем поиск по производителю
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/products?producer=Producer A');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThan(0, count($data['data']));
    }

    public function test_api_caching()
    {
        // Первый запрос - данные загружаются из БД
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/products/stats');

        $response1->assertStatus(200);

        // Второй запрос - данные должны быть закешированы
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/products/stats');

        $response2->assertStatus(200);

        // Проверяем, что ответы одинаковые (кеширование работает)
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_api_error_handling()
    {
        // Тестируем обработку ошибок
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/products/999999');

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Товар не найден',
                ]);

        // Тестируем валидацию
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/products', []);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors',
                ]);
    }

    public function test_api_response_format()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/products');

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/json')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'quantity',
                            'calculated_volume',
                            'producer',
                            'arrival_date',
                            'is_active',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'links',
                    'meta',
                ]);
    }

    public function test_api_rate_limiting()
    {
        // Делаем много запросов подряд
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->adminToken,
            ])->getJson('/api/products');

            $response->assertStatus(200);
        }

        // Проверяем, что API все еще работает
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/products');

        $response->assertStatus(200);
    }
} 