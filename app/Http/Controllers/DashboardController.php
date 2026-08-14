<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'owner') {
            return view('owner.dashboard', [
                'user' => $user,
            ]);
        }

        return view('karyawan.dashboard', [
            'user' => $user,
        ]);
    }
}