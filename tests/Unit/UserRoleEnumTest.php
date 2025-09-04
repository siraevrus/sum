<?php

namespace Tests\Unit;

use App\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleEnumTest extends TestCase
{
    public function test_enum_has_expected_values(): void
    {
        $values = array_map(fn ($case) => $case->value, UserRole::cases());

        $this->assertContains('admin', $values);
        $this->assertContains('operator', $values);
        $this->assertContains('warehouse_worker', $values);
        $this->assertContains('sales_manager', $values);
    }
}


