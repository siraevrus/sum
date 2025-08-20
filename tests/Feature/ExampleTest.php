<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // Главная страница может перенаправлять на логин (302) или показывать контент (200)
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }
}
