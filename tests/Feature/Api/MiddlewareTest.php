<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_compress_middleware_compresses_json_response()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);
        
        // Проверяем, что ответ сжат (gzip)
        $this->assertTrue(
            $response->headers->has('Content-Encoding') &&
            $response->headers->get('Content-Encoding') === 'gzip'
        );
    }

    public function test_authentication_middleware_blocks_unauthenticated_requests()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
    }

    public function test_authentication_middleware_allows_authenticated_requests()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/products');

        $response->assertStatus(200);
    }

    public function test_invalid_token_returns_401()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/products');

        $response->assertStatus(401);
    }

    public function test_missing_token_returns_401()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }

    public function test_public_routes_dont_require_authentication()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422); // Валидация не прошла, но не 401
    }

    public function test_cors_headers_are_present()
    {
        $response = $this->getJson('/api/products');

        $response->assertHeader('Access-Control-Allow-Origin');
    }
} 