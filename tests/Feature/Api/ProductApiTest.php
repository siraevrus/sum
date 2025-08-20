<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Company $company;
    private Warehouse $warehouse;
    private ProductTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
        $this->template = ProductTemplate::factory()->create();
    }

    public function test_can_get_products_list()
    {
        Product::factory()->count(3)->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products');

        $response->assertStatus(200)
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

    public function test_can_get_single_product()
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $product->id,
                    'name' => $product->name,
                ]);
    }

    public function test_can_create_product()
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'quantity' => 10,
            'producer' => 'Test Producer',
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'arrival_date' => now()->toDateString(),
            'attributes' => [
                'length' => 100,
                'width' => 50,
                'height' => 25,
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/products', $productData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'product' => [
                        'id',
                        'name',
                        'description',
                        'quantity',
                        'producer',
                    ],
                ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'quantity' => 10,
            'producer' => 'Test Producer',
        ]);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        $updateData = [
            'name' => 'Updated Product',
            'description' => 'Updated Description',
            'quantity' => 20,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'product' => [
                        'id',
                        'name',
                        'description',
                        'quantity',
                    ],
                ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'description' => 'Updated Description',
            'quantity' => 20,
        ]);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Товар удален',
                ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_can_get_products_stats()
    {
        Product::factory()->count(5)->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'quantity' => 10,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products/stats');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_products',
                    'total_quantity',
                    'total_volume',
                    'low_stock_count',
                    'out_of_stock_count',
                ]);
    }

    public function test_can_get_popular_products()
    {
        Product::factory()->count(3)->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products/popular');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'total_sales',
                            'total_revenue',
                        ],
                    ],
                ]);
    }

    public function test_product_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/products', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['warehouse_id', 'product_template_id']);
    }

    public function test_product_not_found()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products/999');

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Товар не найден',
                ]);
    }

    public function test_unauthorized_access_denied()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }
} 