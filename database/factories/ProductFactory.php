<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $template = ProductTemplate::inRandomOrder()->first();
        $warehouse = Warehouse::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        // Генерируем характеристики на основе шаблона
        $attributes = [];
        if ($template) {
            foreach ($template->formulaAttributes as $attribute) {
                switch ($attribute->type) {
                    case 'number':
                        $attributes[$attribute->variable] = $this->faker->randomFloat(2, 0.1, 10.0);
                        break;
                    case 'text':
                        $attributes[$attribute->variable] = $this->faker->word();
                        break;
                    case 'list':
                        $options = json_decode($attribute->options, true);
                        if ($options) {
                            $attributes[$attribute->variable] = $this->faker->randomElement($options);
                        }
                        break;
                }
            }
        }

        return [
            'product_template_id' => $template?->id ?? ProductTemplate::factory(),
            'warehouse_id' => $warehouse?->id ?? Warehouse::factory(),
            'created_by' => $user?->id ?? User::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'attributes' => $attributes,
            'calculated_volume' => $this->faker->optional()->randomFloat(4, 0.001, 100.0),
            'quantity' => $this->faker->numberBetween(1, 100),
            'transport_number' => $this->faker->optional()->regexify('[A-Z]{2}\d{4}'),
            'producer_id' => \App\Models\Producer::inRandomOrder()->first()?->id ?? \App\Models\Producer::factory(),
            'arrival_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => Product::STATUS_IN_STOCK,
            'is_active' => $this->faker->boolean(80), // 80% товаров активны
            'shipping_location' => $this->faker->optional()->city(),
            'shipping_date' => $this->faker->optional()->dateTimeBetween('-10 days', 'now'),
            'expected_arrival_date' => $this->faker->optional()->dateTimeBetween('now', '+10 days'),
            'actual_arrival_date' => $this->faker->optional()->dateTimeBetween('-10 days', 'now'),
            'document_path' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Указываем, что товар активен
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Указываем, что товар неактивен
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Указываем, что товар в остатках
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(1, 50),
            'status' => Product::STATUS_IN_STOCK,
            'is_active' => true,
        ]);
    }

    /**
     * Указываем, что товар закончился
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'status' => Product::STATUS_IN_STOCK,
        ]);
    }

    /**
     * Указываем, что товар в пути
     */
    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_IN_TRANSIT,
        ]);
    }
}
