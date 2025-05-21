<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());
        return new JsonResponse([
            'message' => 'User registered successfully',
            'user' => new UserResource($user)
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return new JsonResponse([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        // Skip loading roles to avoid the undefined method error

        return new JsonResponse([
            'message' => 'Login successful',
            'user' => new UserResource($user)
        ]);
    }

    public function tokenLogin(LoginRequest $request)
    {
        return $this->authService->tokenLogin($request->validated());
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke all tokens
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        // Logout from session
        Auth::guard('web')->logout();

        // Clear and invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear cookies
        $cookie = cookie()->forget('laravel_session');
        $csrfCookie = cookie()->forget('XSRF-TOKEN');
        $ddsbackendCookie = cookie()->forget('ddsbackend_session');

        $response = new JsonResponse([
            'message' => 'Logged out successfully'
        ]);

        return $response->withCookie($cookie)
            ->withCookie($csrfCookie)
            ->withCookie($ddsbackendCookie);
    }

    public function me(Request $request): JsonResponse
    {
        // Ensure response is always JSON by using JsonResponse
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $user = $request->user();

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401, [
                'Content-Type' => 'application/json'
            ]);
        }

        // Skip loading relationships due to method issues
        // We'll just return the user resource as is
        return new JsonResponse([
            'success' => true,
            'user' => new UserResource($user)
        ], 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}
