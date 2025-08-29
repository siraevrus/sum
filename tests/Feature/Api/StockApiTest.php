<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stocks_endpoint_is_accessible(): void
    {
        $response = $this->get('/api/stocks');
        
        // Должен вернуть 302 (redirect) на страницу входа
        $response->assertStatus(302);
    }

    public function test_stocks_endpoint_returns_data_for_authenticated_user(): void
    {
        // Создаем тестовые данные
        $user = User::factory()->create(['role' => 'admin']);
        $company = \App\Models\Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $template = ProductTemplate::factory()->create();
        
        Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'calculated_volume' => 1.5,
            'is_active' => 1,
            'status' => 'in_stock'
        ]);

        $token = $user->createToken('test-token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('/api/stocks');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination'
        ]);
    }

    public function test_stocks_endpoint_with_filters(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = \App\Models\Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $template = ProductTemplate::factory()->create();
        
        Product::factory()->create([
            'product_template_id' => $template->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'calculated_volume' => 1.5,
            'is_active' => 1,
            'status' => 'in_stock'
        ]);

        $token = $user->createToken('test-token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('/api/stocks?warehouse_id=' . $warehouse->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination'
        ]);
    }
}
