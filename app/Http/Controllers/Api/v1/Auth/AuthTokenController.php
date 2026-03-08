<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthTokenController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()
            ->where('email', $credentials['email'])
            ->with('roles')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if (! $user->roles()->where('type', 'commuter')->exists()) {
            return response()->json([
                'message' => 'This account is not allowed to access commuter mobile APIs.',
            ], 403);
        }

        $plainToken = Str::random(60);

        $user->forceFill([
            'api_token' => hash('sha256', $plainToken),
        ])->save();

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'token' => $plainToken,
                'token_type' => 'Bearer',
                'user' => new UserResource($user->load('roles')),
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = request()->user();

        return response()->json([
            'data' => new UserResource($user->loadMissing('roles')),
        ]);
    }

    public function destroy(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = request()->user();

        $user->forceFill([
            'api_token' => null,
        ])->save();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
