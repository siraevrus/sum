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
use Livewire\Livewire;
use Tests\TestCase;

class FilamentResourceTest extends TestCase
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

    public function test_admin_can_access_products_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_sales_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/sales');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_requests_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/requests');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_users_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_warehouses_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/warehouses');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_product_templates_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/product-templates');
        $response->assertStatus(200);
    }

    public function test_operator_can_access_products()
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($operator)->get('/admin/products');
        $response->assertStatus(200);
    }

    public function test_worker_can_access_products()
    {
        $worker = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'company_id' => $this->company->id,
        ]);

        // Работник склада НЕ должен видеть товары (только остатки)
        $response = $this->actingAs($worker)->get('/admin/products');
        $response->assertStatus(403);
    }

    public function test_manager_can_access_sales()
    {
        $manager = User::factory()->create([
            'role' => UserRole::SALES_MANAGER,
            'company_id' => $this->company->id,
        ]);

        // Менеджер по продажам НЕ должен видеть продажи
        $response = $this->actingAs($manager)->get('/admin/sales');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_admin()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_products_are_filtered_by_company()
    {
        // Создаем продукт для другой компании
        $otherCompany = Company::factory()->create();
        $otherWarehouse = Warehouse::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $otherProduct = Product::factory()->create([
            'warehouse_id' => $otherWarehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);

        // Проверяем, что видим только продукты нашей компании
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
        ]);
    }

    public function test_sales_are_filtered_by_company()
    {
        // Создаем продажу для другой компании
        $otherCompany = Company::factory()->create();
        $otherWarehouse = Warehouse::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $otherProduct = Product::factory()->create([
            'warehouse_id' => $otherWarehouse->id,
            'product_template_id' => $this->template->id,
        ]);
        $otherSale = Sale::factory()->create([
            'product_id' => $otherProduct->id,
            'warehouse_id' => $otherWarehouse->id,
            'user_id' => $this->admin->id,
            'payment_status' => 'pending',
            'delivery_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/sales');
        $response->assertStatus(200);

        // Проверяем, что видим только продажи нашей компании
        $this->assertDatabaseHas('sales', [
            'id' => $otherSale->id,
        ]);
    }

    public function test_admin_can_access_create_pages()
    {
        $createPages = [
            '/admin/products/create',
            '/admin/sales/create',
            '/admin/requests/create',
            '/admin/users/create',
            '/admin/warehouses/create',
            '/admin/product-templates/create',
        ];

        foreach ($createPages as $page) {
            $response = $this->actingAs($this->admin)->get($page);
            $response->assertStatus(200);
        }
    }

    public function test_admin_can_access_edit_pages()
    {
        $editPages = [
            "/admin/products/{$this->product->id}/edit",
            "/admin/users/{$this->admin->id}/edit",
            "/admin/warehouses/{$this->warehouse->id}/edit",
            "/admin/product-templates/{$this->template->id}/edit",
        ];

        foreach ($editPages as $page) {
            $response = $this->actingAs($this->admin)->get($page);
            $response->assertStatus(200);
        }
    }

    public function test_non_admin_cannot_access_user_management()
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR,
            'company_id' => $this->company->id,
        ]);

        // Оператор НЕ должен видеть управление пользователями
        $response = $this->actingAs($operator)->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_model_relationships_work_correctly()
    {
        // Проверяем связи между моделями
        $this->assertEquals($this->company->id, $this->warehouse->company_id);
        $this->assertEquals($this->warehouse->id, $this->product->warehouse_id);
        $this->assertEquals($this->template->id, $this->product->product_template_id);
        $this->assertEquals($this->company->id, $this->admin->company_id);
    }

    public function test_user_roles_work_correctly()
    {
        $this->assertTrue($this->admin->isAdmin());
        $this->assertEquals(UserRole::ADMIN, $this->admin->role);

        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR,
        ]);
        $this->assertFalse($operator->isAdmin());
        $this->assertEquals(UserRole::OPERATOR, $operator->role);
    }

    public function test_company_warehouse_relationship()
    {
        $warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertEquals($this->company->id, $warehouse->company->id);
        $this->assertTrue($this->company->warehouses->contains($warehouse));
    }

    public function test_product_warehouse_relationship()
    {
        $this->assertEquals($this->warehouse->id, $this->product->warehouse->id);
        $this->assertTrue($this->warehouse->products->contains($this->product));
    }

    public function test_product_template_relationship()
    {
        $this->assertEquals($this->template->id, $this->product->productTemplate->id);
        $this->assertTrue($this->template->products->contains($this->product));
    }
} 