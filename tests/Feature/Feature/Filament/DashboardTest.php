<?php

namespace Tests\Feature\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    public function test_operator_can_access_dashboard(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);

        $response = $this->get('/admin');
        $response->assertStatus(403);
    }

    public function test_dashboard_shows_navigation_menu(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Компании');
        $response->assertSee('Склады');
        $response->assertSee('Сотрудники');
    }
}
