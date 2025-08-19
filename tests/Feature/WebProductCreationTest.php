<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use App\Models\Company;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebProductCreationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Warehouse $warehouse;
    protected ProductTemplate $template;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->template = ProductTemplate::factory()->create();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function can_access_product_creation_page()
    {
        $response = $this->actingAs($this->admin)->get('/admin/products/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function can_submit_product_creation_form()
    {
        $this->actingAs($this->admin);

        $productData = [
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
            'attributes' => ['test' => 'value'], // Добавляем характеристики для формирования наименования
        ];

        $response = $this->post('/admin/products', $productData);
        
        // Проверяем, что получили редирект (успешное создание)
        $response->assertStatus(302);
        
        // Проверяем, что товар создался
        $this->assertDatabaseHas('products', [
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);
    }

    /** @test */
    public function can_see_products_list()
    {
        // Создаем товар
        $product = Product::create([
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'arrival_date' => now(),
            'is_active' => true,
            'attributes' => ['test' => 'value'], // Добавляем характеристики для формирования наименования
            'name' => 'Test Product', // Добавляем наименование вручную для теста
            'created_by' => $this->admin->id,
        ]);

        // Проверяем, что товар отображается в списке
        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);
        $response->assertSee($product->id);
    }

    /** @test */
    public function debug_form_validation()
    {
        $this->actingAs($this->admin);

        // Тестируем валидацию с неполными данными
        $invalidData = [
            // Отсутствуют обязательные поля
        ];

        $response = $this->post('/admin/products', $invalidData);
        
        // Должны получить ошибки валидации
        $response->assertStatus(302); // Редирект на страницу с ошибками
    }
} 