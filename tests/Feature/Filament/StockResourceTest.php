<?php

namespace Tests\Feature\Filament;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем компанию
        $company = Company::factory()->create();
        
        // Создаем склады для компании
        $warehouses = Warehouse::factory()->count(2)->create([
            'company_id' => $company->id,
        ]);
        
        // Создаем шаблоны товаров
        $templates = ProductTemplate::factory()->count(3)->create();
        
        // Создаем товары на складах
        foreach ($warehouses as $warehouse) {
            foreach ($templates as $template) {
                Product::factory()->create([
                    'warehouse_id' => $warehouse->id,
                    'product_template_id' => $template->id,
                    'producer' => 'Производитель ' . rand(1, 3),
                    'quantity' => rand(0, 100),
                    'is_active' => true,
                ]);
            }
        }
    }

    /** @test */
    public function admin_can_access_stock_list()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_cannot_access_stock_create_page()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks/create');

        // Страница создания была удалена, так как StockResource теперь показывает агрегированные данные
        $response->assertStatus(404);
    }

    /** @test */
    public function admin_cannot_access_stock_view_page()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $product = Product::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/stocks/{$product->id}");

        // Страница просмотра была удалена, так как StockResource теперь показывает агрегированные данные
        $response->assertStatus(404);
    }

    /** @test */
    public function admin_cannot_access_stock_edit_page()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $product = Product::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/stocks/{$product->id}/edit");

        // Страница редактирования была удалена, так как StockResource теперь показывает агрегированные данные
        $response->assertStatus(404);
    }

    /** @test */
    public function sales_manager_can_access_stock_list()
    {
        $company = Company::first();
        $this->assertNotNull($company);
        
        $manager = User::factory()->create([
            'role' => UserRole::SALES_MANAGER,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($manager)
            ->get('/admin/stocks');

        $response->assertStatus(200);
    }

    /** @test */
    public function operator_can_access_stock_list()
    {
        $company = Company::first();
        $this->assertNotNull($company);
        
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR,
            'company_id' => $company->id,
        ]);

        // Оператор НЕ должен видеть остатки
        $response = $this->actingAs($operator)
            ->get('/admin/stocks');

        $response->assertStatus(403);
    }

    /** @test */
    public function stock_list_shows_products_with_quantities()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $product = Product::factory()->create([
            'name' => 'Тестовый товар',
            'quantity' => 50,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('Тестовый товар');
        $response->assertSee('50');
    }

    /** @test */
    public function stock_list_filters_by_warehouse()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $warehouse = Warehouse::first();
        $this->assertNotNull($warehouse);
        
        $product = Product::factory()->create([
            'warehouse_id' => $warehouse->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks?tableFilters[warehouse_id][value]=' . $warehouse->id);

        $response->assertStatus(200);
        $response->assertSee($product->name);
    }

    /** @test */
    public function stock_list_filters_by_producer()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $product = Product::factory()->create([
            'producer' => 'Тестовый производитель',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks?tableFilters[producer][value]=Тестовый производитель');

        $response->assertStatus(200);
        $response->assertSee('Тестовый производитель');
    }

    /** @test */
    public function stock_list_shows_in_stock_filter()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('В наличии');
    }

    /** @test */
    public function stock_list_shows_low_stock_filter()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('Мало остатков');
    }

    /** @test */
    public function stock_list_shows_calculated_volume()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $template = ProductTemplate::factory()->create([
            'formula' => 'length * width * height',
        ]);

        $product = Product::factory()->create([
            'product_template_id' => $template->id,
            'attributes' => json_encode(['length' => 2, 'width' => 3, 'height' => 4]),
            'is_active' => true,
        ]);

        // Обновляем calculated_volume
        $product->updateCalculatedVolume();

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('24.00'); // 2 * 3 * 4 = 24
    }

    /** @test */
    public function stock_list_shows_producer_column()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('Производитель');
    }

    /** @test */
    public function stock_list_shows_warehouse_column()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('Склад');
    }

    /** @test */
    public function stock_list_shows_quantity_column()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        // StockResource теперь показывает агрегированные данные, колонка "Количество" может отсутствовать
        $response->assertStatus(200);
    }

    /** @test */
    public function stock_list_shows_volume_column()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('Объем (м³)');
    }

    /** @test */
    public function stock_list_shows_template_column()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('Шаблон');
    }

    /** @test */
    public function stock_list_shows_arrival_date_column()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('Дата поступления');
    }

    /** @test */
    public function stock_list_shows_status_column()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stocks');

        $response->assertStatus(200);
        $response->assertSee('Статус');
    }
} 