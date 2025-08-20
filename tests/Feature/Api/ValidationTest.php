<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_registration_validation_rules()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'a', // слишком короткое
            'email' => 'invalid-email', // неверный формат
            'password' => '123', // слишком короткий
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_validation_rules()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['login', 'password']);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'invalid-email',
            'password' => '', // пустой пароль
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['login', 'password']);
    }

    public function test_product_creation_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/products', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['warehouse_id', 'product_template_id']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/products', [
            'quantity' => -1, // отрицательное количество
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['quantity']);
    }

    public function test_sale_creation_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sales', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['warehouse_id', 'quantity', 'cash_amount', 'nocash_amount']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sales', [
            'warehouse_id' => 1,
            'quantity' => 0, // нулевое количество
            'cash_amount' => -100, // отрицательная сумма
            'nocash_amount' => 0,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['quantity', 'cash_amount']);
    }

    public function test_profile_update_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/auth/profile', [
            'name' => '', // пустое имя
            'email' => 'invalid-email', // неверный email
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_email_uniqueness_validation()
    {
        // Создаем первого пользователя
        $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Пытаемся создать второго пользователя с тем же email
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_password_confirmation_validation()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    public function test_quantity_must_be_positive()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/products', [
            'quantity' => -5,
            'warehouse_id' => 1,
            'product_template_id' => 1,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['quantity']);
    }

    public function test_price_must_be_positive()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sales', [
            'warehouse_id' => 1,
            'quantity' => 5,
            'cash_amount' => -100,
            'nocash_amount' => 0,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['cash_amount']);
    }

    public function test_phone_number_format_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sales', [
            'warehouse_id' => 1,
            'quantity' => 5,
            'cash_amount' => 100,
            'nocash_amount' => 0,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['warehouse_id']);
    }

    public function test_date_format_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/products', [
            'warehouse_id' => 1,
            'product_template_id' => 1,
            'arrival_date' => 'invalid-date',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['arrival_date']);
    }
} 