<?php

namespace App\Repositories;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class AuthRepository
{

    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'nik' => $data['nik'],
            'project' => $data['project'],
            'department_id' => $data['department_id'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function login(array $data): JsonResponse
    {
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password']
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        request()->session()->regenerate();

        $user = Auth::user();

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
        ], 200);
    }

    public function tokenLogin(array $data): JsonResponse
    {
        if (!Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password']
        ])) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Load roles, permissions, and department for the user
        $user->load(['roles.permissions', 'permissions', 'department']);

        $token = $user->createToken('apiToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => new UserResource($user),
        ], 200);
    }
}
