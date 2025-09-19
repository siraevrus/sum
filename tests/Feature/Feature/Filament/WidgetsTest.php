<?php

namespace Tests\Feature\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_widget_loads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('DashboardStats');
    }

    public function test_sales_overview_widget_loads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    public function test_latest_sales_widget_loads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    public function test_popular_products_widget_loads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('PopularProducts');
    }
}
