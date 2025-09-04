<?php

namespace Tests\Feature\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_warehouses_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin/warehouses');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_warehouses_list(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);

        $response = $this->get('/admin/warehouses');
        $response->assertStatus(403);
    }
}