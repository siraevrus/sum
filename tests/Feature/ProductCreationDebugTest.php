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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductCreationDebugTest extends TestCase
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
    public function debug_product_creation_step_by_step()
    {
        // Шаг 1: Проверяем, что все модели созданы
        $this->assertDatabaseHas('companies', ['id' => $this->company->id]);
        $this->assertDatabaseHas('warehouses', ['id' => $this->warehouse->id]);
        $this->assertDatabaseHas('product_templates', ['id' => $this->template->id]);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);

        // Шаг 2: Проверяем роль пользователя
        $this->assertEquals('admin', $this->admin->role->value);

        // Шаг 3: Создаем товар с минимальными данными
        $productData = [
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Debug Test Product',
            'quantity' => 1,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
            'attributes' => [], // Добавляем пустой массив атрибутов
        ];

        // Шаг 4: Пытаемся создать товар
        try {
            $product = Product::create(array_merge($productData, [
                'created_by' => $this->admin->id,
            ]));

            // Шаг 5: Проверяем, что товар создался
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'name' => 'Debug Test Product',
            ]);

            // Шаг 6: Проверяем связи
            $this->assertEquals($this->template->id, $product->product_template_id);
            $this->assertEquals($this->warehouse->id, $product->warehouse_id);
            $this->assertEquals($this->admin->id, $product->created_by);

            // Шаг 7: Проверяем, что товар отображается
            $response = $this->actingAs($this->admin)->get('/admin/products');
            $response->assertStatus(200);
            $response->assertSee('Debug Test Product');

        } catch (\Exception $e) {
            // Логируем ошибку
            Log::error('Product creation failed: ' . $e->getMessage());
            $this->fail('Product creation failed: ' . $e->getMessage());
        }
    }

    /** @test */
    public function debug_filament_form_validation()
    {
        // Проверяем валидацию формы Filament
        $formData = [
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Form Test Product',
            'quantity' => 1,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
        ];

        // Проверяем, что данные проходят валидацию
        $validator = Validator::make($formData, [
            'product_template_id' => 'required|exists:product_templates,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'arrival_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $this->assertTrue($validator->passes(), 'Validation failed: ' . $validator->errors()->toJson());
    }

    /** @test */
    public function debug_database_connection()
    {
        // Проверяем подключение к базе данных
        try {
            $count = Product::count();
            $this->assertIsInt($count);
        } catch (\Exception $e) {
            $this->fail('Database connection failed: ' . $e->getMessage());
        }
    }

    /** @test */
    public function debug_user_authentication()
    {
        // Проверяем аутентификацию пользователя
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);

        // Проверяем доступ к ресурсу товаров
        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);
    }
}
