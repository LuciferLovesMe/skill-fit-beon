<?php

namespace Modules\Resident\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Resident\Models\Resident;
use Faker\Factory as Faker;

class ResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        // Inisialisasi Faker dengan locale Indonesia
        $faker = Faker::create('id_ID');

        // 1. Masukkan beberapa data statis (Fix Data) untuk mempermudah testing UI
        $staticResidents = [
            [
                'name'    => 'Budi Santoso',
                'id_card_photo'        => 'ktp/dummy-ktp.jpg',
                'is_permanent' => true,
                'phone_number'   => '081234567890',
                'is_married'  => true,
            ],
            [
                'name'    => 'Siti Aminah',
                'id_card_photo'        => 'ktp/dummy-ktp.jpg',
                'is_permanent' => false,
                'phone_number'   => '081987654321',
                'is_married'  => false,
            ],
            [
                'name'    => 'Ahmad Dahlan',
                'id_card_photo'        => 'ktp/dummy-ktp.jpg',
                'is_permanent' => true,
                'phone_number'   => '081299887766',
                'is_married'  => true,
            ],
        ];

        foreach ($staticResidents as $resident) {
            Resident::create($resident);
        }

        // 2. Generate 12 data dinamis acak menggunakan Faker untuk meramaikan pagination
        for ($i = 0; $i < 12; $i++) {
            Resident::create([
                'name' => $faker->name,
                'id_card_photo' => 'ktp/dummy-ktp.jpg', // Kosongkan dulu untuk dummy
                'is_permanent' => $faker->randomElement([true, false]),
                'phone_number' => $faker->phoneNumber,
                'is_married' => $faker->randomElement([false, true]),
            ]);
        }
    }
}
