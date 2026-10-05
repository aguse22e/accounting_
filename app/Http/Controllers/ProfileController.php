<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        return view('profile', compact('user'));
    }

    public function settings()
    {
        $user = Auth::user();
        return view('settings', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'password' => ['nullable','string','min:6','confirmed'],
        ]);

        $user->User_name = $validated['name'];

        if (!empty($validated['password'])) {
            // Akan di-hash otomatis oleh cast pada model User
            $user->password = $validated['password'];
        }
        $user->save();

        // Refresh & build response data
        $user->refresh();
        $payload = [
            'id' => $user->id,
            'name' => $user->User_name,
            'email' => $user->email,
        ];

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengaturan berhasil disimpan.', 'user' => $payload]);
        }
        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}

