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
use Livewire\Livewire;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class FilamentProductCreationTest extends TestCase
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
    public function can_create_product_via_filament_livewire()
    {
        $this->actingAs($this->admin);

        $productData = [
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'producer' => 'Test Producer',
            'quantity' => 10,
            'transport_number' => 'TR-001',
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
            'description' => 'Test description',
        ];

        // Проверяем, что данные валидны
        $this->assertTrue($this->validateProductData($productData));

        // Создаем товар через Livewire
        $component = Livewire::test(CreateProduct::class)
            ->set('data', $productData)
            ->call('create');

        // Проверяем, что товар создался
        $this->assertDatabaseHas('products', [
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'created_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function can_create_product_with_minimal_data()
    {
        $this->actingAs($this->admin);

        $productData = [
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 1,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
        ];

        // Создаем товар напрямую через модель
        $product = Product::create(array_merge($productData, [
            'created_by' => $this->admin->id,
            'attributes' => [],
        ]));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);

        // Проверяем, что товар отображается в списке
        $response = $this->get('/admin/products');
        $response->assertStatus(200);
        $response->assertSee($product->id);
    }

    /** @test */
    public function debug_form_validation_rules()
    {
        // Проверяем правила валидации для создания товара
        $rules = [
            'product_template_id' => 'required|exists:product_templates,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'arrival_date' => 'required|date',
            'is_active' => 'boolean',
        ];

        $validData = [
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes(), 'Validation failed: ' . $validator->errors()->toJson());
    }

    /** @test */
    public function debug_database_schema()
    {
        // Проверяем структуру таблицы products
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasColumn('products', 'id'));
        $this->assertTrue(Schema::hasColumn('products', 'product_template_id'));
        $this->assertTrue(Schema::hasColumn('products', 'warehouse_id'));
        $this->assertTrue(Schema::hasColumn('products', 'name'));
        $this->assertTrue(Schema::hasColumn('products', 'quantity'));
        $this->assertTrue(Schema::hasColumn('products', 'attributes'));
        $this->assertTrue(Schema::hasColumn('products', 'created_by'));
    }

    private function validateProductData(array $data): bool
    {
        $rules = [
            'product_template_id' => 'required|exists:product_templates,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'arrival_date' => 'required|date',
            'is_active' => 'boolean',
        ];

        $validator = Validator::make($data, $rules);
        return $validator->passes();
    }
}
