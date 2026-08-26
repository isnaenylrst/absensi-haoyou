<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale)
    {
        if (in_array($locale, ['id', 'en'], true)) {
            session(['locale' => $locale]);
        }

        return back();
    }
}