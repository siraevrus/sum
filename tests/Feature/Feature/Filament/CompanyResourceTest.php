<?php

namespace Tests\Feature\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_companies_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin/companies');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_companies_list(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);

        $response = $this->get('/admin/companies');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $companyData = [
            'name' => 'Test Company',
            'inn' => '1234567890',
            'kpp' => '123456789',
            'ogrn' => '1234567890123',
        ];

        $response = $this->post('/admin/companies', $companyData);
        $response->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'inn' => '1234567890',
        ]);
    }
}