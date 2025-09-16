<?php

namespace Tests\Feature\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_stock_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin/stocks');
        $response->assertStatus(200);
    }

    public function test_sales_manager_can_access_stock_list(): void
    {
        $manager = User::factory()->create(['role' => 'sales_manager']);
        $this->actingAs($manager);

        $response = $this->get('/admin/stocks');
        $response->assertStatus(200);
    }

    public function test_operator_can_access_stock_list(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);

        $response = $this->get('/admin/stocks');
        $response->assertStatus(200);
    }
}