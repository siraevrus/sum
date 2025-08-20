<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginByUsernameTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function user_can_login_by_username()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'username',
                    'email',
                    'name',
                ],
                'token',
            ]);

        $this->assertEquals('testuser', $response->json('user.username'));
    }

    /** @test */
    public function user_can_login_by_email()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'username',
                    'email',
                    'name',
                ],
                'token',
            ]);

        $this->assertEquals('test@example.com', $response->json('user.email'));
    }

    /** @test */
    public function login_fails_with_invalid_username()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'invaliduser',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Неверные учетные данные',
            ]);
    }

    /** @test */
    public function login_fails_with_invalid_email()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'invalid@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Неверные учетные данные',
            ]);
    }

    /** @test */
    public function login_fails_with_wrong_password()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Неверные учетные данные',
            ]);
    }

    /** @test */
    public function login_requires_login_field()
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    }

    /** @test */
    public function login_requires_password_field()
    {
        $response = $this->postJson('/api/auth/login', [
            'login' => 'testuser',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function user_can_register_with_username()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'operator',
            'company_id' => $this->company->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'username',
                    'email',
                    'name',
                ],
                'token',
            ]);

        $this->assertEquals('newuser', $response->json('user.username'));
        $this->assertEquals('newuser@example.com', $response->json('user.email'));
    }

    /** @test */
    public function registration_requires_unique_username()
    {
        User::factory()->create([
            'name' => 'Existing User',
            'username' => 'existinguser',
            'email' => 'existing@example.com',
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'username' => 'existinguser', // Дублирующийся логин
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'operator',
            'company_id' => $this->company->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /** @test */
    public function user_can_update_profile_with_username()
    {
        $user = User::factory()->create([
            'name' => 'Old User',
            'username' => 'olduser',
            'email' => 'old@example.com',
            'company_id' => $this->company->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/users/profile', [
            'username' => 'newuser',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(200);
        
        // Проверяем, что запрос прошел успешно
        $this->assertEquals(200, $response->status());
    }
} 