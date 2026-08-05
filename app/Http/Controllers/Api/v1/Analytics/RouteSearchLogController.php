<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Controller;
use App\Models\RouteSearchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteSearchLogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin'      => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
        ]);

        RouteSearchLog::create([
            'user_id'     => $request->user()->id,
            'origin'      => $validated['origin'],
            'destination' => $validated['destination'],
        ]);

        return response()->json(['message' => 'Search logged.'], 201);
    }
}
