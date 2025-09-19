<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_no_correction_by_default(): void
    {
        $product = Product::factory()->create();

        $this->assertFalse($product->hasCorrection());
        $this->assertNull($product->correction);
        $this->assertNull($product->correction_status);
    }

    public function test_product_can_add_correction(): void
    {
        $product = Product::factory()->create();
        $correctionText = 'Это тестовое уточнение для товара';

        $result = $product->addCorrection($correctionText);

        $this->assertTrue($result);
        $this->assertTrue($product->hasCorrection());
        $this->assertEquals($correctionText, $product->correction);
        $this->assertEquals('correction', $product->correction_status);
    }

    public function test_product_can_remove_correction(): void
    {
        $product = Product::factory()->create([
            'correction' => 'Тестовое уточнение',
            'correction_status' => 'correction',
        ]);

        $result = $product->removeCorrection();

        $this->assertTrue($result);
        $this->assertFalse($product->hasCorrection());
        $this->assertNull($product->correction);
        $this->assertNull($product->correction_status);
    }

    public function test_has_correction_returns_false_when_correction_status_is_not_correction(): void
    {
        $product = Product::factory()->create([
            'correction' => 'Тестовое уточнение',
            'correction_status' => 'none',
        ]);

        $this->assertFalse($product->hasCorrection());
    }

    public function test_has_correction_returns_false_when_correction_is_empty(): void
    {
        $product = Product::factory()->create([
            'correction' => '',
            'correction_status' => 'correction',
        ]);

        $this->assertFalse($product->hasCorrection());
    }
}
