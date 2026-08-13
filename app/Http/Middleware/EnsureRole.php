<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        if ($user->status_akun !== 'aktif') {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda dinonaktifkan. Hubungi Owner/HR.',
            ]);
        }

        return $next($request);
    }
}