<?php

namespace App\Http\Controllers\Api\Jumuishi;

use App\Http\Controllers\Controller;
use App\Services\Jumuishi\JumuishiUserProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserSyncController extends Controller
{
    public function __construct(private JumuishiUserProvisioner $provisioner) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_uuid' => ['required', 'uuid'],
            'event_type' => ['required', 'string', 'max:64'],
            'global_user_id' => ['nullable', 'integer', 'min:1'],
            'email' => ['nullable', 'email', 'max:255'],
            'password_hash' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:32'],
            'module' => ['nullable', 'string', 'max:64'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'second_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:64'],
            'token_version' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $user = $this->provisioner->applyLifecycleEvent($validated);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'local_user_id' => $user?->id,
                ],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to sync user lifecycle event.',
            ], 500);
        }
    }
}
