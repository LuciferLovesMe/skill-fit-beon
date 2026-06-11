<?php

namespace Modules\House\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\House\Models\House;
use Modules\House\Models\OccupancyHistory;

class HouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = House::query()->with('occupancyHistories.resident', 'currentOccupant.resident');
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where('name', 'like', "%{$search}%");
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            $perPage = $request->get('per_page', 10);
            $houses = $query->latest()->paginate($perPage);
            return response()->json([
                'success' => true,
                'message' => 'Daftar rumah berhasil diambil.',
                'data' => $houses->items(),
                'meta' => [
                    'current_page' => $houses->currentPage(),
                    'last_page'    => $houses->lastPage(),
                    'per_page'     => $houses->perPage(),
                    'total'        => $houses->total(),
                    'has_more'     => $houses->hasMorePages()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar rumah.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('house::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) 
    {
        $validate = $request->validate([
            'block_number' => 'required|string|max:10',
            'is_occupied' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            $house = House::create([
                'block_number' => $request->get('block_number'),
                'is_occupied' => $request->get('is_occupied'),
            ]);
            $house->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data rumah berhasil disimpan.',
                'data' => $house
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error menyimpan data rumah: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data rumah.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error menyimpan data rumah: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        try {
            $house = House::with('occupancyHistories.resident')->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Data rumah berhasil diambil.',
                'data' => $house
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data rumah.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data rumah tidak ditemukan.',
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('house::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) 
    {
        $validate = $request->validate([
            'block_number' => 'required|string|max:10',
            'is_occupied' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only(['block_number', 'is_occupied']);
            $house = House::findOrFail($id);
            $house->update($data);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data rumah berhasil diperbarui.',
                'data' => $house
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error memperbarui data rumah: ' . $e->getMessage());    
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data rumah.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data rumah tidak ditemukan.',
            ], 404);
        } catch (\Illuminate\validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error memperbarui data rumah: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $e->errors()
            ], 422); 
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) 
    {
        DB::beginTransaction();
        try {
            $house = House::findOrFail($id);
            $house->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data rumah berhasil dihapus.',
            ], 200);
         } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error menghapus data rumah: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data rumah.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data rumah tidak ditemukan.',
            ], 404);   
        }
    }

    public function assignResident (Request $request, $id) 
    {
        $validate = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'start_date' => 'required|date|date_format:Y-m-d',
        ]);

        DB::beginTransaction();

        try {
            $house = House::findOrFail($id);
            $residentId = $request->get('resident_id');
            $startDate = $request->get('start_date');

            $isOccupiedElsewhere = OccupancyHistory::where('resident_id', $residentId)
                ->whereNull('end_date')
                ->where('house_id', '!=', $id)
                ->first();

            if ($isOccupiedElsewhere) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Penghuni sudah menempati rumah lain.',
                ], 422);
            }

            $activeOccupancy = OccupancyHistory::where('house_id', $id)
                ->whereNull('end_date')
                ->first();

            if ($activeOccupancy) {
                $oldEndDate = date('Y-m-d', strtotime('-1 day', strtotime($startDate)));
                $activeOccupancy->update([
                    'end_date' => $oldEndDate >= $activeOccupancy->end_date ? $activeOccupancy->end_date : $oldEndDate
                ]);
            }

            OccupancyHistory::create([
                'house_id' => $id,
                'resident_id' => $residentId,
                'start_date' => $startDate,
            ]);

            $house->update(['is_occupied' => true]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengalokasikan penghuni baru.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error mengalokasikan penghuni baru: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengalokasikan penghuni baru.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error mengalokasikan penghuni baru: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $e->errors()
            ], 422); 
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data rumah tidak ditemukan.',
            ], 404);   
        }
    }

    function removeResident(Request $request, $id) 
    {
        DB::beginTransaction();
        try {    
            $house = House::findOrFail($id);
            
            $validate = $request->validate([
                'end_date' => 'required|date|date_format:Y-m-d',
            ]);

            $activeOccupancy = OccupancyHistory::where('house_id', $id)
                ->whereNull('end_date')
                ->first();
            if (!$activeOccupancy) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada penghuni aktif di rumah ini.',
                ], 422);
            }

            $endDate = $request->get('end_date');

            if ($endDate < $activeOccupancy->start_date) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal keluar tidak boleh sebelum tanggal masuk.',
                ], 422);
            }

            $activeOccupancy->update(['end_date' => $endDate]);
            $house->update(['is_occupied' => false]);
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Berhasil menghapus penghuni dari rumah.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error menghapus penghuni: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus penghuni.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data rumah tidak ditemukan.',
            ], 404);   
        } catch (\Illuminate\validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error menghapus penghuni: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $e->errors()
            ], 422); 
        }
    }

    public function occupancyHistories($id)
    {
        try {
            $house = House::findOrFail($id);
            $histories = $house->occupancyHistories()->with('resident')->get();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat hunian berhasil diambil.',
                'data' => $histories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat hunian.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data rumah tidak ditemukan.',
            ], 404);   
        }
    }

    public function paymentHistories($id)
    {
        try {
            $house = House::findOrFail($id);
            $histories = $house->paymentHistories()->with('resident')->get();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat pembayaran berhasil diambil.',
                'data' => $histories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat pembayaran.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data rumah tidak ditemukan.',
            ], 404);   
        }
    }
}
