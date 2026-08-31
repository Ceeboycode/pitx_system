<?php

namespace App\Http\Controllers\Api\V2\Driver\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Register a new driver account and return a Sanctum token.
     */
    public function register(Request $request): JsonResponse
    {
        // Validate the incoming request data before creating anything.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Make sure the driver role exists. If it doesn't, create it.
        $role = Role::firstOrCreate([
            'name' => 'driver',
            'guard_name' => 'web',
        ], [
            'type' => 'driver',
        ]);

        // Create the user record in the database.
        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']), // hash the password for security
            'company_id' => null,
            'status' => 'active',
        ]);

        // Assign the driver role to the new user.
        $user->assignRole($role);

        // Create a Sanctum token for the mobile app to use.
        $token = $user->createToken('driver-mobile', ['*'])->plainTextToken;

        // Return a JSON response to the app.
        return response()->json([
            'message' => 'Driver registered successfully.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->getRoleNames()->first(),
                ],
            ],
        ], 201); // 201 = created successfully
    }

    /**
     * Log in an existing driver and return a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        // Validate the login request.
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Try to find the user by email or username.
        $user = User::query()
            ->where('email', $validated['login'])
            ->orWhere('username', $validated['login'])
            ->first();

        // If no user exists, or the password is wrong, return an error.
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422); // 422 = validation / auth failed
        }

        // Make sure this account is actually a driver.
        if (! $user->hasRole('driver')) {
            return response()->json([
                'message' => 'This account is not allowed for the driver API.',
            ], 403); // 403 = forbidden
        }

        // Create a fresh Sanctum token for the app.
        $token = $user->createToken('driver-mobile', ['*'])->plainTextToken;

        // Return the token and the user info.
        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->getRoleNames()->first(),
                ],
            ],
        ]);
    }

    /**
     * Return the currently authenticated driver's profile information.
     */
    public function me(Request $request): JsonResponse
    {
        // Laravel will get the logged-in user from the Sanctum token.
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->getRoleNames()->first(),
            ],
        ]);
    }

    /**
     * Log out the current driver by deleting their current token.
     */
    public function logout(Request $request): JsonResponse
    {
        // Delete the current Sanctum token so the device can no longer use it.
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
