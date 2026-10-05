<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Akun;
use App\Models\ActivityUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AkunController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $akun = Akun::orderBy('kode_akun')->get();
        return response()->json([
            'success' => true,
            'data' => $akun
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('akun.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validasi lengkap
            $validated = $request->validate([
                'kode_akun' => ['required', 'regex:/^[0-9]{1,4}$/', 'unique:kode_akun,kode_akun'],
                'nama' => ['required', 'string', 'max:40'],
                'tipe' => ['required', 'in:Debit,Kredit'],
            ], [
                'kode_akun.required' => 'Kode Akun wajib diisi.',
                'kode_akun.regex' => 'Kode Akun hanya boleh berisi angka, maksimal 4 digit.',
                'kode_akun.unique' => 'Kode Akun sudah digunakan.',
                'nama.required' => 'Nama Akun wajib diisi.',
                'nama.max' => 'Nama Akun maksimal 40 karakter.',
                'tipe.required' => 'Tipe Akun wajib diisi.',
                'tipe.in' => 'Tipe Akun harus Debit atau Kredit.',
            ]);

            $akun = Akun::create($validated);

            // Log activity
            $userId = Auth::id() ?? optional(User::first())->id;
            if ($userId) {
                ActivityUser::logActivity('CREATE', 'Akun', $akun->kode_akun, null, $akun->toArray(), $userId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil ditambahkan',
                'data' => $akun
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $akun = Akun::find($id);
        if (!$akun) {
            return response()->json(['error' => 'Akun tidak ditemukan'], 404);
        }
        return response()->json($akun);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $akun = Akun::find($id);
        if (!$akun) {
            return response()->json(['error' => 'Akun tidak ditemukan'], 404);
        }
        return response()->json($akun);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $akun = Akun::findOrFail($id);
            $oldData = $akun->toArray();
            
            // Validate request data
            $validated = $request->validate([
                'kode_akun' => ['required', 'regex:/^[0-9]{1,4}$/', 'unique:kode_akun,kode_akun,' . $akun->kode_akun . ',kode_akun'],
                'nama' => ['required', 'string', 'max:40'],
                'tipe' => ['required', 'in:Debit,Kredit'],
            ], [
                'kode_akun.required' => 'Kode Akun wajib diisi.',
                'kode_akun.regex' => 'Kode Akun hanya boleh berisi angka, maksimal 4 digit.',
                'kode_akun.unique' => 'Kode Akun sudah digunakan.',
                'nama.required' => 'Nama Akun wajib diisi.',
                'nama.max' => 'Nama Akun maksimal 40 karakter.',
                'tipe.required' => 'Tipe Akun wajib diisi.',
                'tipe.in' => 'Tipe Akun harus Debit atau Kredit.',
            ]);
            
            // Update akun - paksa updated_at selalu refresh
            $akun->fill($validated);
            $akun->updated_at = now();
            $akun->save();
            $akun->refresh();
            
            // Log activity
            $userId = Auth::id() ?? optional(User::first())->id;
            if ($userId) {
                ActivityUser::logActivity('UPDATE', 'Akun', $akun->kode_akun, $oldData, $akun->toArray(), $userId);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil diperbarui',
                'data' => $akun
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage(),
                'errors' => []
            ], 422);
        }
    }

    public function destroy(string $id)
    {
        try {
            $akun = Akun::findOrFail($id);
            $oldData = $akun->toArray();
            
            // Soft delete
            $akun->delete();
            
            // Log activity
            $userId = Auth::id() ?? optional(User::first())->id;
            if ($userId) {
                ActivityUser::logActivity('DELETE', 'Akun', $oldData['kode_akun'], $oldData, null, $userId);
            }
            
            return response()->json(['success' => true, 'message' => 'Akun berhasil dihapus']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get akun by kode_akun
     */
    public function getByKode($kode)
    {
        $akun = Akun::where('kode_akun', $kode)->first();
        if (!$akun) {
            return response()->json(['error' => 'Akun tidak ditemukan'], 404);
        }
        return response()->json(['success' => true, 'data' => $akun]);
    }
}
