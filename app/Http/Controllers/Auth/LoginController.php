<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use function password_get_info;
use function hash_equals;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
		$credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

		// Normalize email to avoid whitespace/case issues
		$credentials['email'] = strtolower(trim($credentials['email']));

		if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
			return redirect()->intended(route('dashboard'));
        }

		// Fallback: upgrade legacy plaintext passwords without changing schema
		$user = User::where('email', $credentials['email'])->first();
		if ($user) {
			$storedPassword = $user->getAuthPassword();
			$info = password_get_info($storedPassword);
			$looksLikePlaintext = ($info['algo'] === 0); // not a password_hash output
			if ($looksLikePlaintext && hash_equals($storedPassword, $credentials['password'])) {
				$user->password = $credentials['password']; // will be hashed by cast
				$user->save();
				Auth::login($user, $request->boolean('remember'));
				$request->session()->regenerate();
				return redirect()->intended(route('dashboard'));
			}

			// Secondary fallback: hashed password matches but Auth::attempt failed
			if (Hash::check($credentials['password'], $storedPassword)) {
				Auth::login($user, $request->boolean('remember'));
				$request->session()->regenerate();
				return redirect()->intended(route('dashboard'));
			}
		}

        return redirect('/')
            ->with('error', 'Email atau password salah.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}


