<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_panel()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_operator_can_access_admin_panel()
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR->value,
        ]);

        $response = $this->actingAs($operator)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_warehouse_worker_can_access_admin_panel()
    {
        $worker = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER->value,
        ]);

        $response = $this->actingAs($worker)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_sales_manager_can_access_admin_panel()
    {
        $manager = User::factory()->create([
            'role' => UserRole::SALES_MANAGER->value,
        ]);

        $response = $this->actingAs($manager)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_admin_panel()
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }
} 