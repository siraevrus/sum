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

class FilamentLivewireTest extends TestCase
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
    public function can_create_product_via_livewire()
    {
        $this->actingAs($this->admin);

        $productData = [
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'arrival_date' => now()->format('Y-m-d'),
            'is_active' => true,
        ];

        // Создаем товар через Livewire компонент
        $component = Livewire::test(CreateProduct::class)
            ->set('data', $productData)
            ->call('create');

        // Проверяем, что товар создался
        $this->assertDatabaseHas('products', [
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
        ]);
    }

    /** @test */
    public function can_access_create_page()
    {
        $response = $this->actingAs($this->admin)->get('/admin/products/create');
        $response->assertStatus(200);
        $response->assertSee('Создать товар');
    }

    /** @test */
    public function can_see_products_in_list()
    {
        // Создаем товар напрямую
        $product = Product::create([
            'product_template_id' => $this->template->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Test Product for List',
            'quantity' => 5,
            'arrival_date' => now(),
            'is_active' => true,
            'attributes' => [],
            'created_by' => $this->admin->id,
        ]);

        // Проверяем, что товар отображается в списке
        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);
        $response->assertSee('Test Product for List');
    }

    /** @test */
    public function debug_user_permissions()
    {
        $this->actingAs($this->admin);

        // Проверяем права доступа к ресурсу
        $canViewAny = \App\Filament\Resources\ProductResource::canViewAny();
        $this->assertTrue($canViewAny, 'User should be able to view products');

        // Проверяем, что пользователь имеет правильную роль
        $this->assertEquals('admin', $this->admin->role->value);

        // Проверяем, что пользователь связан с компанией
        $this->assertNotNull($this->admin->company_id);
    }
} 