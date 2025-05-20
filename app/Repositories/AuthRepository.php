<?php

namespace App\Repositories;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthRepository
{

    public function register(array $data)
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

    public function login(array $data)
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
            'user' => new UserResource($user->load('roles')),
        ], 200);
    }

    public function tokenLogin(array $data)
    {
        if (!Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password']
        ]))  {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        $token = $user->createToken('apiToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => new UserResource($user->load('roles')),
        ], 200);
        
    }
}