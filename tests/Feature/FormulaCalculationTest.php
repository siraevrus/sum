<?php

namespace Tests\Feature;

use App\Models\ProductTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormulaCalculationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_calculate_simple_formula()
    {
        $template = ProductTemplate::factory()->create([
            'formula' => 'a * b * c'
        ]);

        $result = $template->testFormula([
            'a' => 2,
            'b' => 3,
            'c' => 4
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(24, $result['result']);
    }

    /** @test */
    public function it_can_calculate_complex_formula()
    {
        $template = ProductTemplate::factory()->create([
            'formula' => '(a + b) * c / 2'
        ]);

        $result = $template->testFormula([
            'a' => 10,
            'b' => 20,
            'c' => 4
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(60, $result['result']);
    }

    /** @test */
    public function it_handles_missing_variables()
    {
        $template = ProductTemplate::factory()->create([
            'formula' => 'a * b * c'
        ]);

        $result = $template->testFormula([
            'a' => 2,
            'b' => 3
            // c отсутствует
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Отсутствуют переменные: c', $result['error']);
    }

    /** @test */
    public function it_handles_invalid_formula()
    {
        $template = ProductTemplate::factory()->create([
            'formula' => 'a * b +' // Неполная формула
        ]);

        $result = $template->testFormula([
            'a' => 2,
            'b' => 3
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Синтаксическая ошибка', $result['error']);
    }

    /** @test */
    public function it_handles_empty_formula()
    {
        $template = ProductTemplate::factory()->create([
            'formula' => null
        ]);

        $result = $template->testFormula([
            'a' => 2,
            'b' => 3
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Формула не задана', $result['error']);
    }
}
