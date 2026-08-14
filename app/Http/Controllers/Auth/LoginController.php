<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $attempt = [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'status_akun' => 'aktif',
        ];

        if (! Auth::attempt($attempt)) {
            return back()
                ->withErrors(['username' => 'Username, password salah, atau akun nonaktif.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $user = Auth::user();
        if ($user instanceof User) {
            $user->last_login = now();
            $user->save();
        }
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}