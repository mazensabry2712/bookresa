<?php

namespace Database\Factories\Domain\Customer\Models;

use App\Domain\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The model that this factory creates.
     *
     * @var class-string<Customer>
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * The Customer model is tenant-scoped, so tenant_id is intentionally
     * omitted here and is populated by BelongsToTenant from the current
     * tenant context during model creation.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '01'.fake()->numerify('#########');

        return [
            'normalized_phone' => preg_replace('/\D+/', '', '20'.$phone),
            'name' => fake()->name(),
            'phone' => $phone,
            'email' => fake()->unique()->safeEmail(),
            'metadata' => [],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }

    /**
     * Create a customer without an email address.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => null,
        ]);
    }
}
