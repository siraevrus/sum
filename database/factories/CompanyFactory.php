<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'legal_address' => $this->faker->address(),
            'postal_address' => $this->faker->address(),
            'phone_fax' => $this->faker->phoneNumber(),
            'general_director' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'inn' => $this->faker->numerify('##########'),
            'kpp' => $this->faker->numerify('#########'),
            'ogrn' => $this->faker->numerify('##########'),
            'bank' => $this->faker->company(),
            'account_number' => $this->faker->numerify('##################'),
            'correspondent_account' => $this->faker->numerify('##################'),
            'bik' => $this->faker->numerify('#########'),
            'is_archived' => false,
            'archived_at' => null,
        ];
    }

    /**
     * Indicate that the company is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }
} 