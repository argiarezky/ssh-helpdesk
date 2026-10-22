<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemHealthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (Throwable) {
            return response()->json([
                'application' => 'online',
                'database' => 'offline',
            ], 503);
        }

        return response()->json([
            'application' => 'online',
            'database' => 'online',
        ]);
    }
}
