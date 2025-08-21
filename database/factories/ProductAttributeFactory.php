<?php

namespace Database\Factories;

use App\Models\ProductAttribute;
use App\Models\ProductTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['number', 'text', 'select'];
        $type = $this->faker->randomElement($types);

        $attributeData = [
            'product_template_id' => ProductTemplate::factory(),
            'variable' => $this->faker->unique()->word(),
            'name' => $this->faker->words(2, true),
            'type' => $type,
            'is_required' => $this->faker->boolean(),
            'options' => null,
        ];

        if ($type === 'select') {
            $attributeData['options'] = json_encode([
                $this->faker->word(),
                $this->faker->word(),
                $this->faker->word(),
            ]);
        }

        return $attributeData;
    }

    /**
     * Indicate that the attribute is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Indicate that the attribute is optional.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }

    /**
     * Indicate that the attribute is numeric.
     */
    public function numeric(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'number',
        ]);
    }

    /**
     * Indicate that the attribute is text.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
        ]);
    }

    /**
     * Indicate that the attribute is select.
     */
    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'select',
            'options' => json_encode([
                'Опция 1',
                'Опция 2',
                'Опция 3',
            ]),
        ]);
    }
}
