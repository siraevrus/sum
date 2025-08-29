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

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Company $company;
    private Warehouse $warehouse;
    private ProductTemplate $template;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
        $this->template = ProductTemplate::factory()->create();
        
        $this->product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'quantity' => 100,
        ]);
    }

    public function test_can_get_sales_list()
    {
        Sale::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/sales');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'sale_number',
                            'product_id',
                            'warehouse_id',
                            'user_id',
                            'customer_name',
                            'quantity',
                            'unit_price',
                            'total_price',
                            'payment_status',
                            'delivery_status',
                            'sale_date',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'links',
                    'meta',
                ]);
    }

    public function test_can_get_single_sale()
    {
        $sale = Sale::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/sales/{$sale->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                ]);
    }

    public function test_can_create_sale()
    {
        $saleData = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '+7 999 123-45-67',
            'customer_email' => 'customer@example.com',
            'quantity' => 5,
            'unit_price' => 1000.00,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'delivery_status' => 'pending',
            'sale_date' => now()->toDateString(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sales', $saleData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'sale' => [
                        'id',
                        'customer_name',
                        'quantity',
                        'unit_price',
                    ],
                ]);

        $this->assertDatabaseHas('sales', [
            'customer_name' => 'Test Customer',
            'quantity' => 5,
            'unit_price' => 1000.00,
        ]);
    }

    public function test_can_update_sale()
    {
        $sale = Sale::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
        ]);

        $updateData = [
            'customer_name' => 'Updated Customer',
            'quantity' => 10,
            'unit_price' => 1500.00,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/sales/{$sale->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'sale' => [
                        'id',
                        'customer_name',
                        'quantity',
                        'unit_price',
                    ],
                ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'customer_name' => 'Updated Customer',
            'quantity' => 10,
            'unit_price' => 1500.00,
        ]);
    }

    public function test_can_delete_sale()
    {
        $sale = Sale::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/sales/{$sale->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Продажа удалена',
                ]);

        $this->assertDatabaseMissing('sales', [
            'id' => $sale->id,
        ]);
    }

    public function test_can_process_sale()
    {
        $sale = Sale::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
            'payment_status' => 'pending',
            'quantity' => 5,
        ]);

        $initialQuantity = $this->product->quantity;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/sales/{$sale->id}/process");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Продажа оформлена',
                ]);

        // Проверяем, что товар списан со склада
        $this->product->refresh();
        $this->assertEquals(5, $this->product->sold_quantity); // 0 + 5 = 5

        // Проверяем, что статус продажи изменился
        $sale->refresh();
        $this->assertEquals('paid', $sale->payment_status);
    }

    public function test_can_cancel_sale()
    {
        $sale = Sale::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
            'payment_status' => 'paid',
            'quantity' => 5,
        ]);

        $initialQuantity = $this->product->quantity;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/sales/{$sale->id}/cancel");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Продажа отменена',
                ]);

        // Проверяем, что товар возвращен на склад
        $this->product->refresh();
        $this->assertEquals(0, $this->product->sold_quantity); // 5 - 5 = 0

        // Проверяем, что статус продажи изменился
        $sale->refresh();
        $this->assertEquals('cancelled', $sale->payment_status);
    }

    public function test_can_get_sales_stats()
    {
        Sale::factory()->count(5)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
            'payment_status' => 'paid',
            'total_price' => 1000.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/sales/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_sales',
                'total_revenue',
                'average_sale',
                'pending_payments',
                'in_delivery',
            ]);
    }

    public function test_can_export_sales()
    {
        Sale::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/sales/export');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id', 'sale_number', 'customer_name', 'customer_phone', 'customer_email',
                        'product_name', 'warehouse', 'quantity', 'unit_price', 'total_price',
                        'payment_status', 'delivery_status', 'payment_method', 'sale_date',
                        'delivery_date', 'created_by', 'created_at'
                    ]
                ],
                'total'
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_cannot_process_sale_with_insufficient_stock()
    {
        $sale = Sale::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
            'user_id' => $this->user->id,
            'payment_status' => 'pending',
            'quantity' => 1000, // Больше чем есть на складе
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/sales/{$sale->id}/process");

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Недостаточно товара на складе',
                ]);
    }

    public function test_sale_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sales', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['product_id', 'warehouse_id', 'customer_name', 'quantity', 'unit_price']);
    }

    public function test_sale_not_found()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/sales/999');

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Продажа не найдена',
                ]);
    }

    public function test_unauthorized_access_denied()
    {
        $response = $this->getJson('/api/sales');

        $response->assertStatus(401);
    }
} 