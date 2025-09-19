<?php

namespace Database\Factories;

use App\Models\ProductTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductTemplate>
 */
class ProductTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'formula' => 'length * width * height',
            'unit' => $this->faker->randomElement(['шт', 'кг', 'м', 'л', 'м³']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
