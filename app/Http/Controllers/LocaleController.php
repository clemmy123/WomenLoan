<?php

namespace App\Http\Controllers;

use App\Support\AccessibleHome;
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

        return redirect()->to($this->intendedRedirect($request));
    }

    private function intendedRedirect(Request $request): string
    {
        $redirect = $request->query('redirect');

        if (is_string($redirect) && $redirect !== '' && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return $redirect;
        }

        $previous = url()->previous();

        if ($previous && $previous !== $request->fullUrl()) {
            return $previous;
        }

        return $request->user()
            ? AccessibleHome::url($request->user())
            : route('login');
    }
}
