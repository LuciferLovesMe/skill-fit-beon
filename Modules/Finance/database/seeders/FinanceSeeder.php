<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;

class FinanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();
        $faker = Faker::create('id_ID');
        $year = 2026;

        // 1. AMBIL DATA HISTORI HUNIAN AKTIF DARI MODUL HOUSE
        // Kita ambil warga yang saat ini masih menghuni rumah (end_date / tanggal_selesai = null)
        $occupancies = DB::table('occupancy_histories')
            ->whereNull('end_date') // Sesuaikan jika nama kolom Anda 'tanggal_selesai'
            ->get();

        // ==============================================================
        // 2. SEEDER PAYMENTS (IURAN WARGA)
        // ==============================================================
        $payments = [];
        
        foreach ($occupancies as $occ) {
            // Generate tagihan untuk 12 bulan (Jan - Des)
            for ($month = 1; $month <= 12; $month++) {
                
                // LOGIKA PEMBAYARAN: 
                // Asumsi saat ini bulan Juni. Maka bulan 1-6 sudah dibayar (Lunas).
                // Bulan 7-12 adalah tagihan yang akan datang (Belum Dibayar).
                $isPaid = $month <= 6;
                $paymentDate = $isPaid ? Carbon::create($year, $month, rand(1, 10))->format('Y-m-d') : null;

                // A. Tagihan Kebersihan (Rp 15.000)
                $payments[] = [
                    'house_id'      => $occ->house_id,
                    'resident_id'   => $occ->resident_id,
                    'fee_type'      => 'cleaning',
                    'month'         => $month,
                    'year'          => $year,
                    'billed_amount' => 15000,
                    'paid_amount'   => $isPaid ? 15000 : null,
                    'payment_date'  => $paymentDate,
                    'is_paid'       => $isPaid ? 1 : 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                // B. Tagihan Satpam (Rp 100.000)
                $payments[] = [
                    'house_id'      => $occ->house_id,
                    'resident_id'   => $occ->resident_id,
                    'fee_type'      => 'security',
                    'month'         => $month,
                    'year'          => $year,
                    'billed_amount' => 100000,
                    'paid_amount'   => $isPaid ? 100000 : null,
                    'payment_date'  => $paymentDate,
                    'is_paid'       => $isPaid ? 1 : 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
        }
        
        // Insert massal agar lebih cepat
        DB::table('payments')->insert($payments);


        // ==============================================================
        // 3. SEEDER EXPENSES (PENGELUARAN KAS RT)
        // ==============================================================
        $expenses = [];

        for ($month = 1; $month <= 12; $month++) {
            $namaBulan = Carbon::create(null, $month)->translatedFormat('F'); // Contoh: "Januari"

            // A. Pengeluaran Rutin (Setiap Bulan)
            $expenses[] = [
                'description'  => "Gaji Satpam Bulan " . $namaBulan,
                'amount'       => 2500000,
                'expense_date' => Carbon::create($year, $month, 2)->format('Y-m-d'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            $expenses[] = [
                'description'  => "Listrik Pos Satpam & Fasum Bulan " . $namaBulan,
                'amount'       => 250000,
                'expense_date' => Carbon::create($year, $month, 5)->format('Y-m-d'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            $expenses[] = [
                'description'  => "Iuran Sampah Desa Bulan " . $namaBulan,
                'amount'       => 150000,
                'expense_date' => Carbon::create($year, $month, 10)->format('Y-m-d'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            // B. Pengeluaran Insidental / Acak (Hanya terjadi di beberapa bulan)
            // Peluang 60% ada pengeluaran tak terduga setiap bulannya
            if (rand(1, 100) <= 60) {
                $insidentalDesc = $faker->randomElement([
                    'Perbaikan Lampu Jalan Blok A',
                    'Perbaikan Selokan Blok B',
                    'Konsumsi Rapat Pengurus RT',
                    'Fotocopy Berkas Laporan RT',
                    'Pembelian Alat Kebersihan (Sapu, Pengki, Trash Bag)',
                    'Kerja Bakti Warga (Konsumsi)'
                ]);

                $expenses[] = [
                    'description'  => $insidentalDesc,
                    'amount'       => $faker->randomElement([50000, 85000, 120000, 150000, 200000, 350000]),
                    'expense_date' => Carbon::create($year, $month, rand(11, 28))->format('Y-m-d'),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        // Insert massal
        DB::table('expenses')->insert($expenses);
    }
}
