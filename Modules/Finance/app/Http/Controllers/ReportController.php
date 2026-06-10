<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Payment;

class ReportController extends Controller
{
    public function getYearlySummary (Request $request)
    {
        try {
            $year = $request->get('year', date('Y'));

            $income = Payment::select(
                    'month',
                    DB::raw('SUM(paid_amount) as total_income')
                )
                ->whereYear('payment_date', $year)
                ->groupBy('month')
                ->pluck('total_income', 'month')
                ->toArray();

            $outcome = Expense::select(
                    DB::raw('month(expense_date) as month'),
                    DB::raw('SUM(amount) as total_outcome')
                )
                ->whereYear('expense_date', $year)
                ->groupBy('month')
                ->pluck('total_outcome', 'month')
                ->toArray();

            $monthsName = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];

            $chartData = [];
            $cumulativeBalance = 0;

            for($i = 1; $i <= 12; $i++) {
                $incomeAmount = $income[$i] ?? 0;
                $outcomeAmount = $outcome[$i] ?? 0;
                $cumulativeBalance += ($incomeAmount - $outcomeAmount);

                $chartData[] = [
                    'monthNumber' => $i,
                    'monthName' => $monthsName[$i],
                    'income' => $incomeAmount,
                    'outcome' => $outcomeAmount,
                    'balance' => $cumulativeBalance
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menyusun ringkasan laporan tahunan: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error fetching yearly summary: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMonthlySummary (Request $request)
    {
        $validate = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:' . date('Y')
        ]);

        try {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));

            $incomeList = Payment::with(['house', 'resident'])
                ->where('month', $month)
                ->where('year', $year)
                ->where('is_paid', true)
                ->latest('payment_date')
                ->get();

            $outcomeList = Expense::whereMonth('expense_date', $month)
                ->whereYear('expense_date', $year)
                ->latest('expense_date')
                ->get();

            $totalIncome = $incomeList->sum('paid_amount');
            $totalOutcome = $outcomeList->sum('amount');
            $balance = $totalIncome - $totalOutcome;

            return response()->json([
                'success' => true,
                'message' => "Detail transaksi untuk bulan {$month}/{$year} berhasil diambil.",
                'data' => [
                    'summary' => [
                        'total_income' => $totalIncome,
                        'total_outcome' => $totalOutcome,
                        'balance' => $balance
                    ],
                    'income_list' => $incomeList,
                    'outcome_list' => $outcomeList
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Gagal menyusun ringkasan laporan bulanan: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error fetching monthly summary: ' . $e->getMessage()
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            Log::warning('Validasi input untuk laporan bulanan gagal: ' . $ve->getMessage(), ['exception' => $ve]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $ve->getMessage(),
                'errors' => $ve->errors()
            ], 422);
        }
    }
}
