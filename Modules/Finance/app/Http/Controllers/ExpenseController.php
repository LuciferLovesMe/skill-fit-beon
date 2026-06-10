<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Expense;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Expense::query();

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where('description', 'like', "%{$search}%");
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereDate('date', '>=', $request->get('start_date'))
                      ->whereDate('date', '<=', $request->get('end_date'));
            }

            $perPage = $request->get('per_page', 10);

            $expenses = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Daftar pengeluaran berhasil diambil.',
                'data' => $expenses->items(),
                'meta' => [
                    'current_page' => $expenses->currentPage(),
                    'last_page'    => $expenses->lastPage(),
                    'per_page'     => $expenses->perPage(),
                    'total'        => $expenses->total(),
                    'has_more'     => $expenses->hasMorePages()
                ]
            ], 200);
        } catch (\Exception $e) {   
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengeluaran: ' . $e->getMessage(),
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
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $expense = Expense::create($validate);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil disimpan.',
                'data' => $expense
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pengeluaran: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $e->getMessage(),
                'errors' => $e->errors()
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
    public function update(Request $request, $id) 
    {
        $validate = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $expense = Expense::findOrFail($id);
            $expense->update($validate);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil diperbarui.',
                'data' => $expense
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengeluaran: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $mnfe) {
            return response()->json([
                'success' => false,
                'message' => 'Pengeluaran tidak ditemukan.',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) 
    {
        DB::beginTransaction();
        try {
            $expense = Expense::findOrFail($id);
            $expense->delete();
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil dihapus.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran: ' . $e->getMessage(),
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $mnfe) {
            return response()->json([
                'success' => false,
                'message' => 'Pengeluaran tidak ditemukan.',
            ], 404);
        }
    }
}
