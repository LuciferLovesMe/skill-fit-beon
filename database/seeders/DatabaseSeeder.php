<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Resident\Database\Seeders\ResidentSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::updateOrCreate(
        //     ['email' => 'admin@rt.com'], // Patokan agar tidak ganda
        //     [
        //         'name' => 'Bapak RT',
        //         'password' => Hash::make('password123'), // Password default
        //     ]
        // );
        $this->call([
            ResidentSeeder::class,
        ]);
    }
}
