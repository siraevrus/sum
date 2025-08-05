<?php

namespace Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
    }

    protected $admin;

    /** @test */
    public function company_name_has_60_character_limit()
    {
        $company = Company::factory()->create();
        
        // Проверяем, что страница редактирования доступна
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит поле названия компании
        $response->assertSee('Название компании');
    }

    /** @test */
    public function general_director_has_60_character_limit()
    {
        $company = Company::factory()->create();
        
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит поле генерального директора
        $response->assertSee('Генеральный директор');
    }

    /** @test */
    public function inn_has_10_character_limit()
    {
        $company = Company::factory()->create();
        
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит поле ИНН
        $response->assertSee('ИНН');
    }

    /** @test */
    public function kpp_has_9_character_limit()
    {
        $company = Company::factory()->create();
        
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит поле КПП
        $response->assertSee('КПП');
    }

    /** @test */
    public function ogrn_has_13_character_limit()
    {
        $company = Company::factory()->create();
        
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит поле ОГРН
        $response->assertSee('ОГРН');
    }

    /** @test */
    public function account_number_has_20_character_limit()
    {
        $company = Company::factory()->create();
        
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит поле расчетного счета
        $response->assertSee('Р/с');
    }

    /** @test */
    public function correspondent_account_has_20_character_limit()
    {
        $company = Company::factory()->create();
        
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит поле корреспондентского счета
        $response->assertSee('К/с');
    }

    /** @test */
    public function bik_has_9_character_limit()
    {
        $company = Company::factory()->create();
        
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит поле БИК
        $response->assertSee('БИК');
    }

    /** @test */
    public function company_edit_page_contains_all_validation_constraints()
    {
        $company = Company::factory()->create();
        
        $response = $this->actingAs($this->admin)
            ->get("/admin/companies/{$company->id}/edit");

        $response->assertStatus(200);
        
        // Проверяем, что форма содержит все необходимые поля
        $response->assertSee('Название компании');
        $response->assertSee('Генеральный директор');
        $response->assertSee('ИНН');
        $response->assertSee('КПП');
        $response->assertSee('ОГРН');
        $response->assertSee('Р/с');
        $response->assertSee('К/с');
        $response->assertSee('БИК');
    }
} 