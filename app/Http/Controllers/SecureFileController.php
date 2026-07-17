<?php

namespace App\Http\Controllers;

use App\Services\SecureFileAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    public function show(string $path, SecureFileAccessService $access): StreamedResponse
    {
        $decoded = base64_decode(strtr($path, '-_', '+/'), true);

        if (! is_string($decoded) || $decoded === '' || str_contains($decoded, '..')) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($decoded)) {
            abort(404);
        }

        abort_unless($access->canAccess(Auth::user(), $decoded), 403);

        return Storage::disk('public')->response($decoded);
    }
}
