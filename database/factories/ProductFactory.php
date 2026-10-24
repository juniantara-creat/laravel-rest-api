<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'sku' => strtoupper(fake()->unique()->bothify('PRD-####??')),
            'category' => fake()->randomElement(['Makanan', 'Minuman', 'Elektronik']),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 1000, 1000000),
            'stock' => fake()->numberBetween(0, 500),
            'photo_path' => null,
        ];
    }
}
