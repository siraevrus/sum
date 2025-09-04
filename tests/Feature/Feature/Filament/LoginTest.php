<?php

namespace Tests\Feature\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_requires_authentication(): void
    {
        $response = $this->get('/admin/login');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_can_access_resource(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }
}
