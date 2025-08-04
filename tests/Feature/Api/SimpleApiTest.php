<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_routes_exist()
    {
        // Проверяем, что API маршруты доступны
        $response = $this->get('/api/auth/login');
        $this->assertNotEquals(404, $response->getStatusCode());

        $response = $this->get('/api/auth/register');
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_user_model_has_sanctum_trait()
    {
        $user = User::factory()->create();
        
        // Проверяем, что модель User имеет метод createToken
        $this->assertTrue(method_exists($user, 'createToken'));
        
        // Проверяем, что можно создать токен
        $token = $user->createToken('test-token');
        $this->assertNotNull($token);
    }

    public function test_user_registration_validation()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password', 'role', 'company_id']);
    }

    public function test_user_login_validation()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_routes_require_token()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }

    public function test_user_can_register_with_valid_data()
    {
        $company = Company::factory()->create();
        
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'operator',
            'company_id' => $company->id,
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(422); // Валидация не прошла
    }

    public function test_authenticated_user_can_access_protected_routes()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_api_returns_proper_error_messages()
    {
        // Тест на неверные учетные данные
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Неверные учетные данные.',
                ]);

        // Тест на неверный формат email
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_api_supports_json_responses()
    {
        $response = $this->getJson('/api/auth/login');

        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_api_handles_missing_authentication()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
    }
} 