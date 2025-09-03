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

class BusinessLogicTest extends TestCase
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
        $this->template = ProductTemplate::factory()->create([
            'formula' => 'length * width * height',
        ]);
        $this->product = Product::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'quantity' => 100,
            'is_active' => true,
            'attributes' => [
                'length' => 10,
                'width' => 5,
                'height' => 3,
            ],
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_product_volume_calculation()
    {
        // Отладочная информация
        $this->assertNotNull($this->product->productTemplate);
        $this->assertNotNull($this->product->productTemplate->formula);
        $this->assertNotNull($this->product->attributes);
        $this->assertIsArray($this->product->attributes);
        
        // Проверяем, что productTemplate загружен в calculateVolume
        $this->assertNotNull($this->product->productTemplate, 'productTemplate is null in calculateVolume');
        $this->assertNotNull($this->product->productTemplate->formula, 'formula is null in calculateVolume');
        
        $testResult = $this->product->productTemplate->testFormula($this->product->attributes);
        $this->assertTrue($testResult['success'], 'Formula test failed: ' . ($testResult['error'] ?? 'Unknown error'));
        
        $volume = $this->product->calculateVolume();
        $this->assertNotNull($volume, 'calculateVolume returned null. Attributes: ' . json_encode($this->product->attributes));
        $this->assertEquals(150.0, $volume);
        
        $this->product->updateCalculatedVolume();
        $this->assertEquals(150.0, $this->product->calculated_volume); // 10 * 5 * 3
    }

    public function test_product_total_volume()
    {
        $this->product->updateCalculatedVolume();
        $this->assertEquals(15000.0, $this->product->getTotalVolume()); // 150 * 100
    }

    public function test_product_stock_management()
    {
        $this->assertTrue($this->product->hasStock());
        
        $this->product->decreaseQuantity(50);
        $this->assertEquals(50, $this->product->quantity);
        
        $this->product->increaseQuantity(25);
        $this->assertEquals(75, $this->product->quantity);
    }

    public function test_product_cannot_decrease_below_zero()
    {
        $result = $this->product->decreaseQuantity(150);
        $this->assertFalse($result);
        $this->assertEquals(100, $this->product->quantity);
    }

    public function test_sale_reduces_product_stock()
    {
        $initialStock = $this->product->quantity;
        
        $sale = Sale::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'quantity' => 20,
            'payment_status' => 'pending',
        ]);

        // Обрабатываем продажу
        $sale->processSale();

        $this->product->refresh();
        $this->assertEquals($initialStock - 20, $this->product->quantity);
    }

    public function test_request_approval_workflow()
    {
        $request = Request::factory()->create([
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'product_template_id' => $this->template->id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'quantity' => 10,
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $request->status);
        
        $request->update(['status' => 'approved']);
        $this->assertEquals('approved', $request->status);
        
        $request->update(['status' => 'completed']);
        $this->assertEquals('completed', $request->status);
    }

    public function test_product_template_formula_testing()
    {
        $testData = [
            'length' => 10,
            'width' => 5,
            'height' => 3,
        ];

        $result = $this->template->testFormula($testData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(150, $result['result']);
    }

    public function test_product_template_invalid_formula()
    {
        $this->template->update(['formula' => 'length * invalid_variable']);
        
        $testData = ['length' => 10];
        $result = $this->template->testFormula($testData);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Отсутствуют переменные', $result['error']);
    }

    public function test_user_role_permissions()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $operator = User::factory()->create(['role' => UserRole::OPERATOR]);
        $worker = User::factory()->create(['role' => UserRole::WAREHOUSE_WORKER]);
        $manager = User::factory()->create(['role' => UserRole::SALES_MANAGER]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($operator->isAdmin());
        $this->assertFalse($worker->isAdmin());
        $this->assertFalse($manager->isAdmin());
    }

    public function test_warehouse_company_relationship()
    {
        $this->assertEquals($this->company->id, $this->warehouse->company->id);
        $this->assertTrue($this->company->warehouses->contains($this->warehouse));
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

    public function test_sale_calculation_with_vat()
    {
        $sale = Sale::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'quantity' => 10,
            'unit_price' => 1000.00,
            'total_price' => 10000.00,
            'vat_rate' => 20.00,
            'vat_amount' => 2000.00,
            'price_without_vat' => 8000.00,
            'payment_status' => 'paid',
        ]);

        $this->assertEquals(10000.00, $sale->total_price);
        $this->assertEquals(2000.00, $sale->vat_amount);
        $this->assertEquals(8000.00, $sale->price_without_vat);
    }

    public function test_product_attributes_management()
    {
        $this->product->setAttributeValue('color', 'red');
        $this->product->setAttributeValue('weight', 5.5);
        
        $this->assertEquals('red', $this->product->getProductAttributeValue('color'));
        $this->assertEquals(5.5, $this->product->getProductAttributeValue('weight'));
        $this->assertNull($this->product->getProductAttributeValue('nonexistent'));
    }

    public function test_product_full_name()
    {
        $this->product->update(['producer' => 'Test Producer']);
        $this->assertEquals($this->product->name . ' (Test Producer)', $this->product->getFullName());
    }

    public function test_warehouse_scope_active()
    {
        $activeWarehouse = Warehouse::factory()->create(['is_active' => true]);
        $inactiveWarehouse = Warehouse::factory()->create(['is_active' => false]);

        $activeWarehouses = Warehouse::active()->get();
        
        $this->assertTrue($activeWarehouses->contains($activeWarehouse));
        $this->assertFalse($activeWarehouses->contains($inactiveWarehouse));
    }

    public function test_product_scope_active()
    {
        $activeProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        $activeProducts = Product::active()->get();
        
        $this->assertTrue($activeProducts->contains($activeProduct));
        $this->assertFalse($activeProducts->contains($inactiveProduct));
    }

    public function test_product_scope_in_stock()
    {
        $inStockProduct = Product::factory()->create(['quantity' => 10]);
        $outOfStockProduct = Product::factory()->create(['quantity' => 0]);

        $inStockProducts = Product::inStock()->get();
        
        $this->assertTrue($inStockProducts->contains($inStockProduct));
        $this->assertFalse($inStockProducts->contains($outOfStockProduct));
    }

    public function test_product_template_scope_active()
    {
        $activeTemplate = ProductTemplate::factory()->create(['is_active' => true]);
        $inactiveTemplate = ProductTemplate::factory()->create(['is_active' => false]);

        $activeTemplates = ProductTemplate::active()->get();
        
        $this->assertTrue($activeTemplates->contains($activeTemplate));
        $this->assertFalse($activeTemplates->contains($inactiveTemplate));
    }

    public function test_product_producers_list()
    {
        Product::factory()->create(['producer' => 'Producer A']);
        Product::factory()->create(['producer' => 'Producer B']);
        Product::factory()->create(['producer' => 'Producer A']); // Дубликат

        $producers = Product::getProducers();
        
        $this->assertContains('Producer A', $producers);
        $this->assertContains('Producer B', $producers);
        $this->assertGreaterThanOrEqual(2, count($producers)); // Может быть больше из-за setUp
    }

    public function test_product_stats()
    {
        Product::factory()->create(['is_active' => true, 'quantity' => 10]);
        Product::factory()->create(['is_active' => false, 'quantity' => 5]);
        Product::factory()->create(['is_active' => true, 'quantity' => 0]);

        $stats = Product::getStats();
        
        $this->assertEquals(4, $stats['total_products']); // 3 созданных + 1 из setUp
        $this->assertEquals(3, $stats['active_products']); // 2 активных + 1 из setUp
        $this->assertEquals(3, $stats['products_in_stock']); // 3 с остатками (включая setUp)
        $this->assertEquals(115, $stats['total_quantity']); // 10 + 5 + 0 + 100 из setUp
    }
} 