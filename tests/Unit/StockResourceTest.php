<?php

namespace Tests\Unit;

use App\Filament\Resources\StockResource;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем админа для тестирования
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        
        $this->actingAs($admin);
    }

    public function test_stock_resource_can_be_instantiated(): void
    {
        $resource = new StockResource();
        $this->assertInstanceOf(StockResource::class, $resource);
    }

    public function test_stock_resource_has_correct_model(): void
    {
        $this->assertEquals(Product::class, StockResource::getModel());
    }

    public function test_stock_resource_has_correct_navigation_label(): void
    {
        $this->assertEquals('Остатки', StockResource::getNavigationLabel());
    }

    public function test_stock_resource_has_correct_model_label(): void
    {
        $this->assertEquals('Остаток', StockResource::getModelLabel());
    }

    public function test_stock_resource_has_correct_plural_model_label(): void
    {
        $this->assertEquals('Остатки', StockResource::getPluralModelLabel());
    }

    public function test_stock_resource_has_correct_navigation_sort(): void
    {
        $this->assertEquals(7, StockResource::getNavigationSort());
    }

    public function test_stock_resource_can_view_any_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        $this->assertTrue(StockResource::canViewAny());
    }

    public function test_stock_resource_can_view_any_for_warehouse_worker(): void
    {
        $worker = User::factory()->create(['role' => 'warehouse_worker']);
        $this->actingAs($worker);
        
        $this->assertTrue(StockResource::canViewAny());
    }

    public function test_stock_resource_can_view_any_for_sales_manager(): void
    {
        $manager = User::factory()->create(['role' => 'sales_manager']);
        $this->actingAs($manager);
        
        $this->assertTrue(StockResource::canViewAny());
    }

    public function test_stock_resource_cannot_view_any_for_operator(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);
        
        $this->assertFalse(StockResource::canViewAny());
    }

    public function test_stock_resource_cannot_view_any_for_guest(): void
    {
        $this->assertFalse(StockResource::canViewAny());
    }
}
