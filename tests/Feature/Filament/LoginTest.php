<?php

namespace Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function login_page_shows_username_field()
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Email или логин');
    }

    /** @test */
    public function admin_user_can_access_admin_panel()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
    }

    /** @test */
    public function operator_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
            'role' => UserRole::OPERATOR,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function warehouse_worker_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
            'role' => UserRole::WAREHOUSE_WORKER,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function sales_manager_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
            'role' => UserRole::SALES_MANAGER,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_panel()
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }
} 