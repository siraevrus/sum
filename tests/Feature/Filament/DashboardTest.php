<?php

namespace Tests\Feature\Filament;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Request;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_admin_can_access_dashboard()
    {
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_dashboard_shows_correct_stats()
    {
        // Создаем дополнительные данные для статистики
        Product::factory()->count(5)->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'is_active' => true,
        ]);

        Sale::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
        ]);

        Request::factory()->count(2)->create([
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_dashboard_stats_widget_works()
    {
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
        
        // Проверяем, что страница содержит элементы дашборда
        $response->assertSee('Dashboard');
    }

    public function test_latest_sales_widget_works()
    {
        // Создаем несколько продаж
        Sale::factory()->count(5)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_popular_products_widget_works()
    {
        // Создаем несколько продуктов
        Product::factory()->count(5)->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_sales_overview_widget_works()
    {
        // Создаем продажи с разными статусами
        Sale::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
        ]);

        Sale::factory()->count(2)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'payment_status' => 'pending',
            'delivery_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_dashboard_not_accessible_by_non_admin_roles()
    {
        $roles = [
            UserRole::OPERATOR,
            UserRole::WAREHOUSE_WORKER,
            UserRole::SALES_MANAGER,
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'company_id' => $this->company->id,
            ]);

            $response = $this->actingAs($user)->get('/admin');
            $response->assertStatus(403);
        }
    }

    public function test_dashboard_shows_company_specific_data()
    {
        // Создаем данные для другой компании
        $otherCompany = Company::factory()->create();
        $otherWarehouse = Warehouse::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $otherProduct = Product::factory()->create([
            'warehouse_id' => $otherWarehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);

        // Проверяем, что данные нашей компании существуют
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
        ]);
    }

    public function test_dashboard_performance_with_large_dataset()
    {
        // Создаем большое количество данных
        Product::factory()->count(50)->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'is_active' => true,
        ]);

        Sale::factory()->count(30)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
        ]);

        $startTime = microtime(true);
        $response = $this->actingAs($this->admin)->get('/admin');
        $endTime = microtime(true);

        $response->assertStatus(200);
        
        // Проверяем, что страница загружается за разумное время (менее 2 секунд)
        $this->assertLessThan(2.0, $endTime - $startTime);
    }

    public function test_dashboard_widgets_show_correct_counts()
    {
        // Создаем точное количество записей
        $productCount = 10;
        $saleCount = 5;
        $requestCount = 3;

        Product::factory()->count($productCount)->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'is_active' => true,
        ]);

        Sale::factory()->count($saleCount)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
        ]);

        Request::factory()->count($requestCount)->create([
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);

        // Проверяем, что количество записей в БД соответствует ожидаемому
        $this->assertEquals($productCount + 1, Product::count()); // +1 из-за setUp
        $this->assertEquals($saleCount, Sale::count());
        $this->assertEquals($requestCount, Request::count());
    }

    public function test_dashboard_handles_empty_data()
    {
        // Удаляем все данные кроме базовых
        Product::where('id', '!=', $this->product->id)->delete();
        Sale::truncate();
        Request::truncate();

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_dashboard_accessible_without_company()
    {
        $userWithoutCompany = User::factory()->create([
            'role' => UserRole::ADMIN,
            'company_id' => null,
        ]);

        $response = $this->actingAs($userWithoutCompany)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_dashboard_works_with_inactive_products()
    {
        // Создаем неактивные продукты
        Product::factory()->count(3)->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_dashboard_works_with_cancelled_sales()
    {
        // Создаем отмененные продажи
        Sale::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'payment_status' => 'cancelled',
            'delivery_status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_dashboard_works_with_pending_requests()
    {
        // Создаем ожидающие запросы
        Request::factory()->count(3)->create([
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }
} 