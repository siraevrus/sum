<?php

namespace Tests\Feature\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_product_with_valid_data()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        $response = $this->post(route('filament.admin.resources.products.create'), [
            'warehouse_id' => $warehouse->id,
            'producer_id' => 1,
            'arrival_date' => now()->toDateString(),
            'product_template_id' => $template->id,
            'quantity' => 100,
            'name' => 'Test Product',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('products', [
            'warehouse_id' => $warehouse->id,
            'product_template_id' => $template->id,
            'quantity' => 100,
        ]);
    }

    public function test_quantity_cannot_exceed_limit()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create();

        $response = $this->post(route('filament.admin.resources.products.create'), [
            'warehouse_id' => $warehouse->id,
            'producer_id' => 1,
            'arrival_date' => now()->toDateString(),
            'product_template_id' => $template->id,
            'quantity' => 100000, // превышает лимит
            'name' => 'Test Product',
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_product_name_and_volume_are_generated()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $warehouse = Warehouse::factory()->create();
        $template = ProductTemplate::factory()->create(['name' => 'Доска', 'formula' => 'length * width * height']);

        $response = $this->post(route('filament.admin.resources.products.create'), [
            'warehouse_id' => $warehouse->id,
            'producer_id' => 1,
            'arrival_date' => now()->toDateString(),
            'product_template_id' => $template->id,
            'quantity' => 2,
            'attribute_length' => 2,
            'attribute_width' => 3,
            'attribute_height' => 4,
        ]);

        $response->assertStatus(302);
        $product = Product::latest()->first();
        $this->assertStringContainsString('Доска', $product->name);
        $this->assertEquals(24, $product->calculated_volume);
    }
}
