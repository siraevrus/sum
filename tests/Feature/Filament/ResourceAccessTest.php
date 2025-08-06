<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\ProductTemplate;
use App\Models\Product;
use App\Models\ProductInTransit;
use App\Models\Sale;
use App\Models\Request;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Filament\Facades\Filament;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\WarehouseResource;
use App\Filament\Resources\ProductTemplateResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductInTransitResource;
use App\Filament\Resources\SaleResource;
use App\Filament\Resources\RequestResource;
use App\Filament\Resources\StockResource;
use App\Filament\Resources\ReceiptResource;

class ResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function admin_can_access_all_resources()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($admin);

        // Проверяем доступ к административным ресурсам
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(CompanyResource::canViewAny());
        $this->assertTrue(WarehouseResource::canViewAny());
        $this->assertTrue(ProductTemplateResource::canViewAny());

        // Проверяем доступ к основным ресурсам
        $this->assertTrue(ProductResource::canViewAny());
        $this->assertTrue(ProductInTransitResource::canViewAny());
        $this->assertTrue(SaleResource::canViewAny());
        $this->assertTrue(RequestResource::canViewAny());
        $this->assertTrue(StockResource::canViewAny());
        $this->assertTrue(ReceiptResource::canViewAny());
    }

    /** @test */
    public function operator_can_access_only_limited_resources()
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($operator);

        // Оператор должен видеть только 3 раздела
        $this->assertTrue(ProductResource::canViewAny());
        $this->assertTrue(ProductInTransitResource::canViewAny());
        $this->assertTrue(SaleResource::canViewAny());

        // Оператор НЕ должен видеть административные ресурсы
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(CompanyResource::canViewAny());
        $this->assertFalse(WarehouseResource::canViewAny());
        $this->assertFalse(ProductTemplateResource::canViewAny());

        // Оператор НЕ должен видеть другие ресурсы
        $this->assertFalse(RequestResource::canViewAny());
        $this->assertFalse(StockResource::canViewAny());
        $this->assertFalse(ReceiptResource::canViewAny());
    }

    /** @test */
    public function warehouse_worker_can_access_warehouse_resources()
    {
        $worker = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($worker);

        // Работник склада должен видеть складские ресурсы
        $this->assertTrue(RequestResource::canViewAny());
        $this->assertTrue(StockResource::canViewAny());
        $this->assertTrue(ProductInTransitResource::canViewAny());
        $this->assertTrue(SaleResource::canViewAny());
        $this->assertTrue(ReceiptResource::canViewAny());

        // Работник склада НЕ должен видеть административные ресурсы
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(CompanyResource::canViewAny());
        $this->assertFalse(WarehouseResource::canViewAny());
        $this->assertFalse(ProductTemplateResource::canViewAny());

        // Работник склада НЕ должен видеть товары (только остатки)
        $this->assertFalse(ProductResource::canViewAny());
    }

    /** @test */
    public function sales_manager_can_access_sales_resources()
    {
        $manager = User::factory()->create([
            'role' => UserRole::SALES_MANAGER,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($manager);

        // Менеджер по продажам должен видеть ресурсы продаж
        $this->assertTrue(RequestResource::canViewAny());
        $this->assertTrue(StockResource::canViewAny());
        $this->assertTrue(ProductInTransitResource::canViewAny());

        // Менеджер по продажам НЕ должен видеть административные ресурсы
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(CompanyResource::canViewAny());
        $this->assertFalse(WarehouseResource::canViewAny());
        $this->assertFalse(ProductTemplateResource::canViewAny());

        // Менеджер по продажам НЕ должен видеть другие ресурсы
        $this->assertFalse(ProductResource::canViewAny());
        $this->assertFalse(SaleResource::canViewAny());
        $this->assertFalse(ReceiptResource::canViewAny());
    }

    /** @test */
    public function unauthenticated_user_cannot_access_any_resources()
    {
        // Неавторизованный пользователь не должен видеть ресурсы
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(CompanyResource::canViewAny());
        $this->assertFalse(WarehouseResource::canViewAny());
        $this->assertFalse(ProductTemplateResource::canViewAny());
        $this->assertFalse(ProductResource::canViewAny());
        $this->assertFalse(ProductInTransitResource::canViewAny());
        $this->assertFalse(SaleResource::canViewAny());
        $this->assertFalse(RequestResource::canViewAny());
        $this->assertFalse(StockResource::canViewAny());
        $this->assertFalse(ReceiptResource::canViewAny());
    }
} 