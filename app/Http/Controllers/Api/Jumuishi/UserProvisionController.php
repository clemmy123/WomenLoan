<?php

namespace App\Http\Controllers\Api\Jumuishi;

use App\Http\Controllers\Controller;
use App\Models\JumuishiProcessedEvent;
use App\Services\Jumuishi\JumuishiUserProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserProvisionController extends Controller
{
    public function __construct(private JumuishiUserProvisioner $provisioner) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global_user_id' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'second_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:64'],
            'password_hash' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:32'],
            'event_uuid' => ['required', 'uuid'],
            'event_type' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $user = DB::transaction(function () use ($validated) {
                $existing = JumuishiProcessedEvent::query()
                    ->whereKey($validated['event_uuid'])
                    ->lockForUpdate()
                    ->first();

                if ($existing?->local_user_id) {
                    return $existing->localUser;
                }

                $user = $this->provisioner->upsert($validated);

                if (! $existing) {
                    JumuishiProcessedEvent::query()->create([
                        'event_uuid' => $validated['event_uuid'],
                        'event_type' => $validated['event_type'] ?? 'user.created',
                        'local_user_id' => $user->id,
                    ]);
                }

                return $user;
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'local_user_id' => $user->id,
                ],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to provision user.',
            ], 500);
        }
    }
}
