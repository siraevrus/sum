<?php

namespace Database\Factories;

use App\Models\Request;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Request>
 */
class RequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Request::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();
        $warehouse = Warehouse::inRandomOrder()->first();
        $template = ProductTemplate::inRandomOrder()->first();

        $priorities = [
            Request::PRIORITY_LOW,
            Request::PRIORITY_NORMAL,
            Request::PRIORITY_HIGH,
            Request::PRIORITY_URGENT,
        ];

        $statuses = [
            Request::STATUS_PENDING,
            Request::STATUS_APPROVED,
            Request::STATUS_REJECTED,
            Request::STATUS_IN_PROGRESS,
            Request::STATUS_COMPLETED,
            Request::STATUS_CANCELLED,
        ];

        return [
            'user_id' => $user?->id ?? User::factory(),
            'warehouse_id' => $warehouse?->id ?? Warehouse::factory(),
            'product_template_id' => $template?->id ?? ProductTemplate::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(2),
            'quantity' => $this->faker->numberBetween(1, 20),
            'priority' => $this->faker->randomElement($priorities),
            'status' => $this->faker->randomElement($statuses),
            'admin_notes' => $this->faker->optional()->sentence(),
            'approved_by' => null,
            'processed_by' => null,
            'approved_at' => null,
            'processed_at' => null,
            'completed_at' => null,
            'is_active' => $this->faker->boolean(80), // 80% запросов активны
        ];
    }

    /**
     * Указываем, что запрос ожидает рассмотрения
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Request::STATUS_PENDING,
                'approved_at' => null,
                'processed_at' => null,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Указываем, что запрос одобрен
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Request::STATUS_APPROVED,
                'approved_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'processed_at' => null,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Указываем, что запрос в обработке
     */
    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Request::STATUS_IN_PROGRESS,
                'approved_at' => $this->faker->dateTimeBetween('-30 days', '-7 days'),
                'processed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                'completed_at' => null,
            ];
        });
    }

    /**
     * Указываем, что запрос завершен
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Request::STATUS_COMPLETED,
                'approved_at' => $this->faker->dateTimeBetween('-30 days', '-14 days'),
                'processed_at' => $this->faker->dateTimeBetween('-14 days', '-7 days'),
                'completed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * Указываем, что запрос отклонен
     */
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Request::STATUS_REJECTED,
                'approved_at' => null,
                'processed_at' => null,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Указываем, что запрос срочный
     */
    public function urgent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => Request::PRIORITY_URGENT,
            ];
        });
    }

    /**
     * Указываем, что запрос просрочен
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Request::STATUS_IN_PROGRESS,
                'approved_at' => $this->faker->dateTimeBetween('-30 days', '-20 days'),
                'processed_at' => $this->faker->dateTimeBetween('-20 days', '-10 days'),
                'completed_at' => null,
            ];
        });
    }
} 