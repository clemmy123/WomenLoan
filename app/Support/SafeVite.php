<?php

namespace App\Support;

use Illuminate\Foundation\Vite as BaseVite;
use Illuminate\Support\Facades\Log;
use Throwable;

class SafeVite extends BaseVite
{
    public function __invoke($entrypoints, $buildDirectory = null)
    {
        if (! app()->environment('local')) {
            $this->useHotFile(storage_path('framework/vite.hot'));
        }

        try {
            return parent::__invoke($entrypoints, $buildDirectory);
        } catch (Throwable $e) {
            Log::warning('Vite assets failed to load: '.$e->getMessage(), [
                'exception' => $e::class,
            ]);

            abort(response()->view('errors.500', [], 500));
        }
    }
}
