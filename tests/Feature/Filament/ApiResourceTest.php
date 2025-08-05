<?php

namespace Tests\Feature\Filament;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $company;
    protected Warehouse $warehouse;
    protected ProductTemplate $template;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->template = ProductTemplate::factory()->create();
        $this->product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'quantity' => 100,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_api_products_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/products');
        $response->assertStatus(200);
    }

    public function test_api_sales_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/sales');
        $response->assertStatus(200);
    }

    public function test_api_requests_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/requests');
        $response->assertStatus(200);
    }

    public function test_api_users_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/users');
        $response->assertStatus(200);
    }

    public function test_api_warehouses_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/warehouses');
        $response->assertStatus(200);
    }

    public function test_api_product_templates_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/product-templates');
        $response->assertStatus(200);
    }

    public function test_api_products_stats_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/products/stats');
        $response->assertStatus(200);
    }

    public function test_api_sales_stats_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/sales/stats');
        $response->assertStatus(200);
    }

    public function test_api_products_popular_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/products/popular');
        $response->assertStatus(200);
    }

    public function test_api_auth_me_endpoint()
    {
        $response = $this->actingAs($this->admin)->get('/api/auth/me');
        $response->assertStatus(200);
    }

    public function test_api_products_export()
    {
        $response = $this->actingAs($this->admin)->get('/api/products/export');
        $response->assertStatus(200);
    }

    public function test_api_sales_export()
    {
        $response = $this->actingAs($this->admin)->get('/api/sales/export');
        $response->assertStatus(200);
    }

    public function test_api_products_search()
    {
        $response = $this->actingAs($this->admin)->get('/api/products?search=test');
        $response->assertStatus(200);
    }

    public function test_api_sales_filter_by_date()
    {
        $response = $this->actingAs($this->admin)->get('/api/sales?date_from=2024-01-01&date_to=2024-12-31');
        $response->assertStatus(200);
    }

    public function test_api_products_filter_by_warehouse()
    {
        $response = $this->actingAs($this->admin)->get("/api/products?warehouse_id={$this->warehouse->id}");
        $response->assertStatus(200);
    }

    public function test_api_products_filter_by_template()
    {
        $response = $this->actingAs($this->admin)->get("/api/products?template_id={$this->template->id}");
        $response->assertStatus(200);
    }

    public function test_api_products_pagination()
    {
        $response = $this->actingAs($this->admin)->get('/api/products?page=1&per_page=10');
        $response->assertStatus(200);
    }

    public function test_api_sales_pagination()
    {
        $response = $this->actingAs($this->admin)->get('/api/sales?page=1&per_page=10');
        $response->assertStatus(200);
    }

    public function test_api_requests_pagination()
    {
        $response = $this->actingAs($this->admin)->get('/api/requests?page=1&per_page=10');
        $response->assertStatus(200);
    }

    public function test_api_products_sort_by_name()
    {
        $response = $this->actingAs($this->admin)->get('/api/products?sort=name&order=asc');
        $response->assertStatus(200);
    }

    public function test_api_sales_sort_by_date()
    {
        $response = $this->actingAs($this->admin)->get('/api/sales?sort=sale_date&order=desc');
        $response->assertStatus(200);
    }

    public function test_api_products_filter_by_status()
    {
        $response = $this->actingAs($this->admin)->get('/api/products?is_active=1');
        $response->assertStatus(200);
    }

    public function test_api_sales_filter_by_payment_status()
    {
        $response = $this->actingAs($this->admin)->get('/api/sales?payment_status=pending');
        $response->assertStatus(200);
    }

    public function test_api_requests_filter_by_status()
    {
        $response = $this->actingAs($this->admin)->get('/api/requests?status=pending');
        $response->assertStatus(200);
    }
} 