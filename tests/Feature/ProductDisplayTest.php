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

class ProductDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Warehouse $warehouse;
    protected ProductTemplate $template;
    protected User $admin;
    protected User $operator;

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
        
        $this->operator = User::factory()->create([
            'role' => UserRole::OPERATOR,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function admin_can_see_all_products()
    {
        // Создаем товар
        $product = Product::factory()->create([
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/products');
        
        $response->assertStatus(200);
        $response->assertSee($product->name);
    }

    /** @test */
    public function operator_can_see_products_from_their_company()
    {
        // Создаем товар в складе оператора
        $product = Product::factory()->create([
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->operator)->get('/admin/products');
        
        $response->assertStatus(200);
        $response->assertSee($product->name);
    }

    /** @test */
    public function operator_cannot_see_products_from_other_company()
    {
        // Создаем другую компанию и склад
        $otherCompany = Company::factory()->create();
        $otherWarehouse = Warehouse::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Создаем товар в другом складе
        $product = Product::factory()->create([
            'product_template_id' => $this->template->id,
            'warehouse_id' => $otherWarehouse->id,
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->operator)->get('/admin/products');
        
        $response->assertStatus(200);
        $response->assertDontSee($product->name);
    }

    /** @test */
    public function product_is_saved_correctly()
    {
        $product = Product::factory()->create([
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->admin->id,
            'name' => 'Test Product',
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Test Product',
            'quantity' => 10,
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);
    }

    /** @test */
    public function can_create_product_through_filament_form()
    {
        $productData = [
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Test Product Created',
            'producer' => 'Test Producer',
            'quantity' => 15,
            'transport_number' => 'TR-001',
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
            'description' => 'Test description',
        ];

        $response = $this->actingAs($this->admin)
            ->post('/admin/products', $productData);

        // Проверяем статус ответа
        $response->assertStatus(302); // Редирект после создания

        // Проверяем, что товар создался
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product Created',
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);
    }

    /** @test */
    public function product_creation_works_with_factory()
    {
        // Создаем товар через фабрику
        $product = Product::factory()->create([
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->admin->id,
            'name' => 'Factory Test Product',
        ]);

        // Проверяем, что товар создался
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Factory Test Product',
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        // Проверяем, что товар отображается в списке для админа
        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);
        $response->assertSee('Factory Test Product');
    }

    /** @test */
    public function can_create_product_directly()
    {
        // Создаем товар напрямую через модель
        $product = new Product([
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->admin->id,
            'name' => 'Direct Test Product',
            'producer' => 'Test Producer',
            'quantity' => 10,
            'transport_number' => 'TR-002',
            'arrival_date' => now(),
            'is_active' => true,
            'description' => 'Test description',
            'attributes' => ['length' => 10, 'width' => 5],
        ]);

        $product->save();

        // Проверяем, что товар создался
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Direct Test Product',
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);

        // Проверяем, что товар отображается в списке для админа
        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);
        $response->assertSee('Direct Test Product');
    }

    /** @test */
    public function user_role_and_access_check()
    {
        // Проверяем роль админа
        $this->assertEquals('admin', $this->admin->role->value);
        $this->assertTrue($this->admin->role->value === 'admin');

        // Проверяем роль оператора
        $this->assertEquals('operator', $this->operator->role->value);
        $this->assertTrue($this->operator->role->value === 'operator');

        // Проверяем, что админ может видеть товары
        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);

        // Проверяем, что оператор может видеть товары
        $response = $this->actingAs($this->operator)->get('/admin/products');
        $response->assertStatus(200);

        // Проверяем, что пользователь связан с компанией
        $this->assertEquals($this->company->id, $this->admin->company_id);
        $this->assertEquals($this->company->id, $this->operator->company_id);
    }
}
