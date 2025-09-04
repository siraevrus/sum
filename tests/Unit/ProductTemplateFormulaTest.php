<?php

namespace Tests\Unit;

use App\Models\ProductTemplate;
use Tests\TestCase;

class ProductTemplateFormulaTest extends TestCase
{
    public function test_testFormula_handles_quantity_and_numeric_attributes(): void
    {
        $template = new ProductTemplate([
            'formula' => 'length * width * height * quantity',
        ]);

        $result = $template->testFormula([
            'length' => 2,
            'width' => 3,
            'height' => 4,
            'quantity' => 5,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(120.0, (float) $result['result']);
    }
}


