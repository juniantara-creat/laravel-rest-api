<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Pengguna Demo', 'password' => 'password']
        );

        Product::factory()->count(10)->create(['user_id' => $user->id]);
    }
}
