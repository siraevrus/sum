<?php

namespace Tests\Feature\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivedCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_archived_companies_are_excluded_from_warehouse_options()
    {
        // Arrange
        $company = Company::factory()->create(['is_archived' => false]);
        $archivedCompany = Company::factory()->create(['is_archived' => true]);
        
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $archivedWarehouse = Warehouse::factory()->create(['company_id' => $archivedCompany->id]);
        
        $admin = User::factory()->create(['role' => 'admin']);

        // Act
        $options = Warehouse::optionsForUser($admin);

        // Assert
        $this->assertArrayHasKey($warehouse->id, $options);
        $this->assertArrayNotHasKey($archivedWarehouse->id, $options);
    }

    public function test_archiving_company_hides_its_warehouses()
    {
        // Arrange
        $company = Company::factory()->create(['is_archived' => false]);
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        // Act - Before archiving
        $optionsBefore = Warehouse::optionsForUser($admin);
        $this->assertArrayHasKey($warehouse->id, $optionsBefore);

        // Archive company
        $company->archive();

        // Act - After archiving
        $optionsAfter = Warehouse::optionsForUser($admin);

        // Assert
        $this->assertArrayNotHasKey($warehouse->id, $optionsAfter);
    }

    public function test_restoring_company_shows_its_warehouses_again()
    {
        // Arrange
        $company = Company::factory()->create(['is_archived' => true]);
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        // Act - Before restoring (should not show)
        $optionsBefore = Warehouse::optionsForUser($admin);
        $this->assertArrayNotHasKey($warehouse->id, $optionsBefore);

        // Restore company
        $company->restore();

        // Act - After restoring
        $optionsAfter = Warehouse::optionsForUser($admin);

        // Assert
        $this->assertArrayHasKey($warehouse->id, $optionsAfter);
    }

    public function test_non_admin_users_still_cannot_see_archived_company_warehouses()
    {
        // Arrange
        $company = Company::factory()->create(['is_archived' => false]);
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'role' => 'warehouse_worker',
            'warehouse_id' => $warehouse->id,
            'company_id' => $company->id
        ]);

        // Act - Before archiving
        $optionsBefore = Warehouse::optionsForUser($user);
        $this->assertArrayHasKey($warehouse->id, $optionsBefore);

        // Archive company
        $company->archive();

        // Act - After archiving
        $optionsAfter = Warehouse::optionsForUser($user);

        // Assert - User should not see their own warehouse if company is archived
        $this->assertArrayNotHasKey($warehouse->id, $optionsAfter);
    }
}
