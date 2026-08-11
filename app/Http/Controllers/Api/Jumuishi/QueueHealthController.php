<?php

namespace App\Http\Controllers\Api\Jumuishi;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QueueHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $connection = config('queue.default', 'sync');
        $pending = 0;
        $failed = 0;
        $workerStatus = 'running';
        $error = null;

        try {
            if (Schema::hasTable('jobs')) {
                $pending = (int) DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $failed = (int) DB::table('failed_jobs')->count();
            }

            // sync/database without a dedicated worker process is still acceptable locally.
            if ($connection === 'sync') {
                $workerStatus = 'running';
            }
        } catch (Throwable $e) {
            $workerStatus = 'error';
            $error = 'Unable to read queue tables.';
        }

        return response()->json([
            'worker_status' => $workerStatus,
            'queue_name' => 'default',
            'connection_name' => $connection,
            'pending_jobs' => $pending,
            'reserved_jobs' => 0,
            'failed_jobs' => $failed,
            'oldest_job_age_seconds' => null,
            'last_processed_at' => null,
            'error_message' => $error,
        ]);
    }
}
