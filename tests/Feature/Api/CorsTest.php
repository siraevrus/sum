<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class CorsTest extends TestCase
{
    public function test_preflight_options_is_allowed_for_api_users(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/users');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin');
    }
}


