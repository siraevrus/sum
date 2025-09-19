<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($user);
        $this->get('/admin')->assertStatus(200);
    }
}
