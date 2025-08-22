<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $token;

    private Company $company;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'company_id' => $this->company->id,
        ]);
        $this->token = $this->admin->createToken('test-token')->plainTextToken;

        // Убедимся, что есть шаблон для товара
        ProductTemplate::factory()->create();
    }

    public function test_can_list_receipts(): void
    {
        ProductInTransit::factory()->count(2)->arrived()->create([
            'warehouse_id' => $this->warehouse->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/receipts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_can_show_receipt(): void
    {
        $receipt = ProductInTransit::factory()->arrived()->create([
            'warehouse_id' => $this->warehouse->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/receipts/'.$receipt->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $receipt->id],
            ]);
    }

    public function test_can_receive_receipt(): void
    {
        $receipt = ProductInTransit::factory()->arrived()->create([
            'warehouse_id' => $this->warehouse->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/receipts/'.$receipt->id.'/receive');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Товар принят',
            ]);

        $this->assertDatabaseHas('product_in_transit', [
            'id' => $receipt->id,
            'status' => ProductInTransit::STATUS_RECEIVED,
        ]);

        $this->assertDatabaseHas('products', [
            'warehouse_id' => $this->warehouse->id,
            'name' => $receipt->name,
        ]);
    }
}
