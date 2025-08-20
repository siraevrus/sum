<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
// // use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
	// use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем пользователя-админа
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        
        Sanctum::actingAs($admin);
    }

    public function test_can_get_companies_list(): void
    {
        // Создаем тестовые компании
        Company::factory()->count(3)->create();

        $response = $this->getJson('/api/companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_company(): void
    {
        $companyData = [
            'name' => 'ООО "Тестовая компания"',
            'legal_address' => 'г. Москва, ул. Тестовая, д. 1',
            'email' => 'test@company.com',
            'inn' => '1234567890',
        ];

        $response = $this->postJson('/api/companies', $companyData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Компания успешно создана',
            ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'ООО "Тестовая компания"',
            'inn' => '1234567890',
        ]);
    }

    public function test_can_get_company_by_id(): void
    {
        $company = Company::factory()->create();

        $response = $this->getJson("/api/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
            ]);
    }

    public function test_can_update_company(): void
    {
        $company = Company::factory()->create();
        $updateData = [
            'name' => 'Обновленное название',
            'email' => 'updated@company.com',
        ];

        $response = $this->putJson("/api/companies/{$company->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Компания успешно обновлена',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Обновленное название',
            'email' => 'updated@company.com',
        ]);
    }

    public function test_can_delete_company(): void
    {
        $company = Company::factory()->create();

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Компания успешно удалена',
            ]);

        // Проверяем, что компания архивирована
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_archived' => true
        ]);
    }

    public function test_can_get_company_warehouses(): void
    {
        $company = Company::factory()->create();
        $warehouses = Warehouse::factory()->count(2)->create([
            'company_id' => $company->id,
        ]);

        $response = $this->getJson("/api/companies/{$company->id}/warehouses");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
            ])
            ->assertJson(['success' => true]);

        $responseData = $response->json('data');
        $this->assertCount(2, $responseData);
    }

    public function test_cannot_access_archived_company(): void
    {
        $company = Company::factory()->create(['is_archived' => true]);

        $response = $this->getJson("/api/companies/{$company->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Компания не найдена',
            ]);
    }

    public function test_cannot_access_archived_company_warehouses(): void
    {
        $company = Company::factory()->create(['is_archived' => true]);

        $response = $this->getJson("/api/companies/{$company->id}/warehouses");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Компания архивирована',
            ]);
    }

    public function test_company_validation_requires_name(): void
    {
        $response = $this->postJson('/api/companies', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_company_validation_inn_must_be_unique(): void
    {
        // Создаем первую компанию
        Company::factory()->create(['inn' => '1234567890']);

        // Пытаемся создать вторую с тем же ИНН
        $response = $this->postJson('/api/companies', [
            'name' => 'Другая компания',
            'inn' => '1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['inn']);
    }

    public function test_company_validation_email_format(): void
    {
        $response = $this->postJson('/api/companies', [
            'name' => 'Тестовая компания',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_company_filtering_by_active_status(): void
    {
        Company::factory()->create(['is_archived' => false]);
        Company::factory()->create(['is_archived' => true]);

        $response = $this->getJson('/api/companies?is_active=true');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        
        // Должна быть только одна активная компания
        $this->assertCount(1, $responseData);
        $this->assertFalse($responseData[0]['is_archived']);
    }

    public function test_company_search_by_name(): void
    {
        Company::factory()->create(['name' => 'Alpha Company']);
        Company::factory()->create(['name' => 'Beta Company']);

        $response = $this->getJson('/api/companies?search=Alpha');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        
        // Должна быть найдена только одна компания
        $this->assertCount(1, $responseData);
        $this->assertStringContainsString('Alpha', $responseData[0]['name']);
    }
}
