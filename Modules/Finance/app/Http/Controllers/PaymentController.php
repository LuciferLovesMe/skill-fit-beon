<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\Payment;
use Modules\House\Models\House;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Payment::query()->with(['resident', 'house']);

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->whereHas('resident', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('house', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 10);

            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Daftar pembayaran berhasil diambil.',
                'data' => $payments->items(),
                'meta' => [
                    'current_page' => $payments->currentPage(),
                    'last_page'    => $payments->lastPage(),
                    'per_page'     => $payments->perPage(),
                    'total'        => $payments->total(),
                    'has_more'     => $payments->hasMorePages()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching payments: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('finance::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) 
    {
        $validate = $request->validate([
            'house_id' => 'required|exists:houses,id',
            'fee_type' => 'required|in:cleaning,security',
            'year' => 'required|integer|min:2000|max:2100',
            'start_month' => 'required|integer|min:1|max:12',
            'duration_months' => 'required|integer|min:1|max:12',
        ]);

        DB::beginTransaction();
        try {
            $houseId = $request->get('house_id');
            $feeType = $request->get('fee_type');
            $year = $request->get('year');
            $startMonth = $request->get('start_month');
            $durationMonths = $request->get('duration_months');

            $house = House::with('currentOccupancy.resident')->findOrFail($houseId);
            $resident = $house->currentOccupancy?->resident;

            if (!$resident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada penghuni saat ini di rumah ini.',
                ], 400);
            }

            $feePerMonth = $feeType === 'cleaning' ? 100000 : 150000;

            if ($feeType === 'security') {
                return response()->json([
                    'success' => false,
                    'message' => 'Iuran keamanan harus dibayarkan per bulan.',
                ], 400);
            }

            $savedPayments = [];
            $currentMonth = $startMonth;
            $currentYear = $year;
            
            for ($i = 0; $i < $durationMonths; $i++) {
                if ($currentMonth > 12) {
                    $currentMonth = 1;
                    $currentYear += 1;
                }

                $alreadyPaid = Payment::where('house_id', $houseId)
                    ->where('fee_type', $feeType)
                    ->where('year', $currentYear)
                    ->where('month', $currentMonth)
                    ->where('is_paid', false)
                    ->exists();

                if ($alreadyPaid) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Iuran untuk bulan {$currentMonth}/{$currentYear} sudah ada dan belum dibayar. Silakan selesaikan pembayaran tersebut terlebih dahulu.",
                    ], 400);
                }

                $payment = Payment::updateOrCreate(
                    [
                        'house_id' => $houseId,
                        'fee_type' => $feeType,
                        'year' => $currentYear,
                        'month' => $currentMonth
                    ],
                    [
                        'resident_id' => $resident->id,
                        'billed_amount' => $feePerMonth,
                        'paid_amount' => $feePerMonth,
                        'tanggal_bayar' => now(),
                        'is_paid' => true,
                    ]
                );

                $savedPayments[] = $payment;
                $currentMonth++;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data pembayaran berhasil disimpan.',
                'data' => $savedPayments
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error menyimpan data pembayaran: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data pembayaran: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal.',
                'errors' => $ve->errors()
            ], 422);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('finance::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('finance::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) 
    {
        DB::beginTransaction();
        try {
            $payment = Payment::findOrFail($id);
            $payment->delete();
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data pembayaran berhasil dihapus.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error menghapus data pembayaran: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pembayaran: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $mnfe) {
            return response()->json([
                'success' => false,
                'message' => 'Data pembayaran tidak ditemukan.',
            ], 404);
        }
    }
}
