<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        if (! in_array($locale, ['en', 'sw'])) {
            abort(404);
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        return back();
    }
}
