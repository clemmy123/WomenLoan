<?php

namespace App\Http\Controllers;

use App\Exceptions\NidaException;
use App\Services\Nida\Data\NidaIdentity;
use App\Services\Nida\NidaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NidaController extends Controller
{
    public function __construct(private NidaService $nida) {}

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nin' => ['required', 'string'],
        ]);

        try {
            $turn = $this->nida->startVerification($validated['nin']);
        } catch (NidaException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status_code' => $e->statusCode,
            ], 422);
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json(['data' => $turn->toArray()]);
    }

    public function answer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nin' => ['required', 'string'],
            'session_id' => ['required', 'string'],
            'rq_code' => ['required', 'string'],
            'answer' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $this->nida->answerQuestion(
                $validated['nin'],
                $validated['session_id'],
                $validated['rq_code'],
                $validated['answer'],
            );
        } catch (NidaException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status_code' => $e->statusCode,
            ], 422);
        }

        return response()->json([
            'data' => $result instanceof NidaIdentity
                ? $result->toArray()
                : $result->toArray(),
        ]);
    }
}
