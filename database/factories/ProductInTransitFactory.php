<?php

namespace Database\Factories;

use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductInTransit>
 */
class ProductInTransitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductInTransit::class;

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

        $statuses = [
            ProductInTransit::STATUS_ORDERED,
            ProductInTransit::STATUS_IN_TRANSIT,
            ProductInTransit::STATUS_ARRIVED,
            ProductInTransit::STATUS_RECEIVED,
            ProductInTransit::STATUS_CANCELLED,
        ];

        return [
            'product_template_id' => $template?->id ?? ProductTemplate::factory(),
            'warehouse_id' => $warehouse?->id ?? Warehouse::factory(),
            'created_by' => $user?->id ?? User::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'attributes' => $attributes,
            'calculated_volume' => $this->faker->optional()->randomFloat(4, 0.001, 100.0),
            'quantity' => $this->faker->numberBetween(1, 50),
            'transport_number' => $this->faker->optional()->regexify('[A-Z]{2}\d{4}'),
            'producer' => $this->faker->optional()->company(),

            'tracking_number' => $this->faker->optional()->regexify('[A-Z]{2}\d{8}'),
            'expected_arrival_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'actual_arrival_date' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'status' => $this->faker->randomElement($statuses),
            'notes' => $this->faker->optional()->sentence(),
            'document_path' => null,
            'is_active' => $this->faker->boolean(80), // 80% товаров активны
        ];
    }

    /**
     * Указываем, что товар заказан
     */
    public function ordered(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ProductInTransit::STATUS_ORDERED,
                'actual_arrival_date' => null,
            ];
        });
    }

    /**
     * Указываем, что товар в пути
     */
    public function inTransit(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ProductInTransit::STATUS_IN_TRANSIT,
                'actual_arrival_date' => null,
            ];
        });
    }

    /**
     * Указываем, что товар прибыл
     */
    public function arrived(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ProductInTransit::STATUS_ARRIVED,
                'actual_arrival_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * Указываем, что товар принят
     */
    public function received(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ProductInTransit::STATUS_RECEIVED,
                'actual_arrival_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * Указываем, что товар отменен
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ProductInTransit::STATUS_CANCELLED,
                'actual_arrival_date' => null,
            ];
        });
    }

    /**
     * Указываем, что товар просрочен
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'expected_arrival_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
                'status' => $this->faker->randomElement([
                    ProductInTransit::STATUS_ORDERED,
                    ProductInTransit::STATUS_IN_TRANSIT,
                ]),
                'actual_arrival_date' => null,
            ];
        });
    }
}
