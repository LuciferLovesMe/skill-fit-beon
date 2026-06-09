<?php

namespace Modules\Resident\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Resident\Models\Resident;

class ResidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Resident::query();

        // 1. Fitur Pencarian (berdasarkan Nama Lengkap atau Nomor Telepon)
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nomor_telepon', 'like', "%{$search}%");
            });
        }

        // 2. Fitur Filter berdasarkan Status Penghuni (tetap / kontrak)
        if ($request->filled('status_penghuni')) {
            $query->where('status_penghuni', $request->get('status_penghuni'));
        }

        // 3. Fitur Filter berdasarkan Status Pernikahan (sudah / belum)
        if ($request->filled('status_menikah')) {
            $query->where('status_menikah', $request->get('status_menikah'));
        }

        // 4. Ambil konfigurasi jumlah data per halaman (default: 10 data)
        $perPage = $request->get('per_page', 10);

        // Eksekusi query dengan pagination terurut dari yang terbaru
        $residents = $query->latest()->paginate($perPage);

        // Bungkus response dengan format standard API agar mudah dikonsumsi React
        return response()->json([
            'success' => true,
            'message' => 'Daftar penghuni berhasil diambil.',
            'data' => $residents->items(), // Mengambil list datanya saja
            'meta' => [
                'current_page' => $residents->currentPage(),
                'last_page'    => $residents->lastPage(),
                'per_page'     => $residents->perPage(),
                'total'        => $residents->total(),
                'has_more'     => $residents->hasMorePages()
            ]
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('resident::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) 
    {
        $validate = $request->validate([
            'name' => 'required|string|max:255',
            'id_card_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone_number' => 'required|string|max:20',
            'is_married' => 'required|boolean',
            'is_permanent' => 'required|boolean',
        ]);
        try {
            $path = null;
            if ($request->hasFile('id_card_photo')) {
                $file = $request->file('id_card_photo');
                $path = $file->store('id_card_photos', 'public');
            }
            $resident = Resident::create([
                'name' => $request->get('name'),
                'id_card_photo' => $path,
                'phone_number' => $request->get('phone_number'),
                'is_married' => $request->get('is_married'),
                'is_permanent' => $request->get('is_permanent'),
            ]);
            $resident->save();

            return response()->json([
                'success' => true,
                'message' => 'Data penghuni berhasil disimpan.',
                'data' => $resident
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error menyimpan data penghuni: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data penghuni: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal.',
                'errors' => $ve->errors()
            ], 422);
        } catch (\Illuminate\Http\Exceptions\PostTooLargeException $pte) {
            return response()->json([
                'success' => false,
                'message' => 'Ukuran file terlalu besar. Maksimal 2MB.',
            ], 413);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        try {
            $resident = Resident::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data penghuni berhasil diambil.',
                'data' => $resident
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error mengambil data penghuni: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data penghuni: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $mnfe) {
            return response()->json([
                'success' => false,
                'message' => 'Data penghuni tidak ditemukan.',
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('resident::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) 
    {
        $validate = $request->validate([
            'name' => 'required|string|max:255',
            'id_card_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone_number' => 'required|string|max:20',
            'is_married' => 'required|boolean',
            'is_permanent' => 'required|boolean',
        ]);

        try {
            $data = $request->only(['name', 'phone_number', 'is_married', 'is_permanent']);

            $resident = Resident::findOrFail($id);

            if ($request->hasFile('id_card_photo')) {
                if ($resident->id_card_photo && Storage::disk('public')->exists($resident->id_card_photo)) {
                    Storage::disk('public')->delete($resident->id_card_photo);
                }

                $path = $request->file('id_card_photo')->store('id_card_photos', 'public');
                $data['id_card_photo'] = $path;
            }

            $resident->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Data penghuni berhasil diperbarui.',
                'data' => $resident
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error memperbarui data penghuni: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data penghuni: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal.',
                'errors' => $ve->errors()
            ], 422);
        } catch (\Illuminate\Http\Exceptions\PostTooLargeException $pte) {
            return response()->json([
                'success' => false,
                'message' => 'Ukuran file terlalu besar. Maksimal 2MB.',
            ], 413);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) 
    {
        try {
            $resident = Resident::findOrFail($id);

            if ($resident->id_card_photo && Storage::disk('public')->exists($resident->id_card_photo)) {
                Storage::disk('public')->delete($resident->id_card_photo);
            }

            $resident->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data penghuni berhasil dihapus.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error menghapus data penghuni: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data penghuni: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $mnfe) {
            return response()->json([
                'success' => false,
                'message' => 'Data penghuni tidak ditemukan.',
            ], 404);
        }
    }
}
