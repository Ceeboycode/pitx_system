<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AuthTokenController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = Role::firstOrCreate([
            'name' => 'commuter',
            'guard_name' => 'web',
        ], [
            'type' => 'commuter',
        ]);

        if ((string) $role->type !== 'commuter') {
            $role->update(['type' => 'commuter']);
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            // 'username' => $validated['username'] ?? $this->generateUsername($validated['email']),
            'username' => $validated['username'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
            'company_id' => null,
            'status' => 'active',
        ]);

        $user->assignRole($role);
        $plainToken = $this->issueToken($user);

        return response()->json([
            'message' => 'Registration successful.',
            'data' => [
                'token' => $plainToken,
                'token_type' => 'Bearer',
                'user' => new UserResource($user->load('roles')),
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()
            ->where('username', $credentials['username'])
            ->with('roles')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        // if (! $user->roles()->where('type', 'commuter')->exists()) {
        //     return response()->json([
        //         'message' => 'This account is not allowed to access commuter mobile APIs.',
        //     ], 403);
        // }

        $plainToken = $this->issueToken($user);

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

    public function update(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = request()->user();

        $validated = request()->validate([
            'name' => 'sometimes|string|max:255',
            'phone_number' => ['sometimes', 'nullable', 'regex:/^\+63[0-9]{10}$/'],
            'username' => ['sometimes', 'string', 'max:20', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->fill($validated)->save();

        return response()->json([
            'data' => new UserResource($user->loadMissing('roles')),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->profile_photo_path = $request->file('avatar')->store('avatars', 'public');
        $user->save();

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

    private function issueToken(User $user): string
    {
        $plainToken = Str::random(60);

        $user->forceFill([
            'api_token' => hash('sha256', $plainToken),
        ])->save();

        return $plainToken;
    }

    private function generateUsername(string $email): string
    {
        $base = Str::lower((string) Str::of($email)->before('@')->replaceMatches('/[^a-zA-Z0-9_]/', ''));
        $base = trim($base, '_');

        if ($base === '') {
            $base = 'commuter';
        }

        $username = $base;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.$suffix;
            $suffix++;
        }

        return $username;
    }
}
