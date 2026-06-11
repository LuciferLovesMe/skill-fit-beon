<?php

namespace Modules\House\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\House\Models\House;
use Modules\House\Models\OccupancyHistory;
use Modules\Resident\Models\Resident;
use Carbon\Carbon;

class HouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        // Ambil data warga yang sudah ada (dari ResidentDatabaseSeeder)
        $residents = Resident::take(15)->get();

        // Generate 20 Rumah
        for ($i = 1; $i <= 20; $i++) {
            $isOccupied = $i <= 15; // 15 rumah pertama dihuni, sisanya kosong

            // 1. Buat Data Rumah
            $house = House::create([
                'block_number' => 'Blok A-' . sprintf('%02d', $i),
                'is_occupied' => $isOccupied ? true : false,
            ]);

            // 2. Jika rumah ditentukan sebagai 'dihuni', buat riwayat huniannya
            if ($isOccupied && isset($residents[$i - 1])) {
                OccupancyHistory::create([
                    'house_id'        => $house->id,
                    'resident_id'     => $residents[$i - 1]->id,
                    // Buat tanggal mulai secara acak antara 1 sampai 12 bulan yang lalu
                    'start_date'   => Carbon::now()->subMonths(rand(1, 12))->format('Y-m-d'),
                    'end_date' => null // Masih aktif
                ]);
            }
        }
    }
}
