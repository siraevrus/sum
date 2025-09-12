<?php

namespace Tests\Feature\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use App\Filament\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\Resources\CompanyResource\Pages\EditCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

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

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'name' => 'Test Company',
                'inn' => '1234567890',
                'kpp' => '123456789',
                'ogrn' => '1234567890123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
        ]);
        
        // Проверяем, что INN правильно сохраняется
        $company = \App\Models\Company::where('name', 'Test Company')->first();
        $this->assertEquals('1234567890', $company->inn); // Проверяем, что значение правильное
    }

    public function test_admin_can_edit_company_via_livewire(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create([
            'name' => 'Old Name',
            'inn' => '1234567890',
        ]);
        $this->actingAs($admin);

        Livewire::test(EditCompany::class, ['record' => $company->getKey()])
            ->fillForm([
                'name' => 'New Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'New Name',
        ]);
    }
}