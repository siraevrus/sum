<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::where('quantity', '>', 0)->inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        $paymentMethods = [
            Sale::PAYMENT_METHOD_CASH,
            Sale::PAYMENT_METHOD_CARD,
            Sale::PAYMENT_METHOD_BANK_TRANSFER,
            Sale::PAYMENT_METHOD_OTHER,
        ];

        $paymentStatuses = [
            Sale::PAYMENT_STATUS_PENDING,
            Sale::PAYMENT_STATUS_PAID,
            Sale::PAYMENT_STATUS_PARTIALLY_PAID,
            Sale::PAYMENT_STATUS_CANCELLED,
        ];

        $deliveryStatuses = [
            Sale::DELIVERY_STATUS_PENDING,
            Sale::DELIVERY_STATUS_IN_PROGRESS,
            Sale::DELIVERY_STATUS_DELIVERED,
            Sale::DELIVERY_STATUS_CANCELLED,
        ];

        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 100, 5000);
        $priceWithoutVat = $quantity * $unitPrice;
        $vatRate = 20.00;
        $vatAmount = $priceWithoutVat * ($vatRate / 100);
        $totalPrice = $priceWithoutVat + $vatAmount;

        return [
            'product_id' => $product?->id ?? Product::factory(),
            'warehouse_id' => $product?->warehouse_id ?? Warehouse::factory(),
            'user_id' => $user?->id ?? User::factory(),
            'sale_number' => Sale::generateSaleNumber(),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_email' => $this->faker->email(),
            'customer_address' => $this->faker->address(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'price_without_vat' => $priceWithoutVat,
            'currency' => 'RUB',
            'exchange_rate' => 1.0000,
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'payment_status' => $this->faker->randomElement($paymentStatuses),
            'delivery_status' => $this->faker->randomElement($deliveryStatuses),
            'notes' => $this->faker->optional()->sentence(),
            'invoice_number' => $this->faker->optional()->regexify('INV-\d{6}'),
            'sale_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'delivery_date' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'is_active' => $this->faker->boolean(80), // 80% продаж активны
        ];
    }

    /**
     * Указываем, что продажа оплачена
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => Sale::PAYMENT_STATUS_PAID,
                'delivery_status' => Sale::DELIVERY_STATUS_DELIVERED,
                'delivery_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            ];
        });
    }

    /**
     * Указываем, что продажа ожидает оплаты
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => Sale::PAYMENT_STATUS_PENDING,
                'delivery_status' => Sale::DELIVERY_STATUS_PENDING,
                'delivery_date' => null,
            ];
        });
    }

    /**
     * Указываем, что продажа отменена
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => Sale::PAYMENT_STATUS_CANCELLED,
                'delivery_status' => Sale::DELIVERY_STATUS_CANCELLED,
                'delivery_date' => null,
            ];
        });
    }

    /**
     * Указываем, что продажа в доставке
     */
    public function inDelivery(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => Sale::PAYMENT_STATUS_PAID,
                'delivery_status' => Sale::DELIVERY_STATUS_IN_PROGRESS,
                'delivery_date' => null,
            ];
        });
    }

    /**
     * Указываем, что продажа просрочена
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => Sale::PAYMENT_STATUS_PAID,
                'delivery_status' => Sale::DELIVERY_STATUS_IN_PROGRESS,
                'sale_date' => $this->faker->dateTimeBetween('-30 days', '-10 days'),
                'delivery_date' => null,
            ];
        });
    }
} 