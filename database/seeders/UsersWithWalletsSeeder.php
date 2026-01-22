<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersWithWalletsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);

            // Create wallet with random balance between 100.00 and 5000.00
            $balance = fake()->randomFloat(2, 100, 5000);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => $balance,
            ]);

            $this->command->info("Created user: {$user->name} ({$user->email}) with wallet balance: \${$balance}");
        }
    }
}
