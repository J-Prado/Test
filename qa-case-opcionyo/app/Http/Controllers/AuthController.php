<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Flow A — registration.
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'] ?? explode('@', $data['email'])[0],
            'email' => $data['email'],
            'password' => $data['password'], // hashed by the model cast
        ]);

        return response()->json(['user' => $user], 201);
    }

    // Flow A — login with valid / invalid credentials.
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    // Flow A — protected resource.
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'logged_out']);
    }
}
