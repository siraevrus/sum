<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестового пользователя с ролью admin
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($user);
    }

    public function test_can_archive_company(): void
    {
        $company = Company::factory()->create([
            'is_archived' => false,
        ]);

        $response = $this->postJson("/api/companies/{$company->id}/archive");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Компания успешно архивирована',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_archived' => true,
        ]);
    }

    public function test_cannot_archive_already_archived_company(): void
    {
        $company = Company::factory()->create([
            'is_archived' => true,
        ]);

        $response = $this->postJson("/api/companies/{$company->id}/archive");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Компания уже архивирована',
            ]);
    }

    public function test_can_restore_archived_company(): void
    {
        $company = Company::factory()->create([
            'is_archived' => true,
        ]);

        $response = $this->postJson("/api/companies/{$company->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Компания успешно восстановлена',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_archived' => false,
        ]);
    }

    public function test_cannot_restore_active_company(): void
    {
        $company = Company::factory()->create([
            'is_archived' => false,
        ]);

        $response = $this->postJson("/api/companies/{$company->id}/restore");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Компания не архивирована',
            ]);
    }

    public function test_cannot_delete_company_with_warehouses(): void
    {
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Нельзя удалить компанию с привязанными складами или сотрудниками. Архивируйте или удалите связанные записи.',
            ])
            ->assertJsonStructure([
                'details' => [
                    'warehouses_count',
                    'employees_count',
                    'suggestion',
                ],
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_cannot_delete_company_with_employees(): void
    {
        $company = Company::factory()->create();
        $employee = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Нельзя удалить компанию с привязанными складами или сотрудниками. Архивируйте или удалите связанные записи.',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_can_delete_company_without_dependencies(): void
    {
        $company = Company::factory()->create();

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Компания успешно удалена',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_archived' => true,
        ]);
    }
}
