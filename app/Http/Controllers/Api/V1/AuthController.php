<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    // POST /api/v1/login
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user()->load('entity');

        return $this->success(new UserResource($user), 'Login berhasil');
    }

    // POST /api/v1/logout
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Logout berhasil');
    }

    // GET /api/v1/me
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('entity');

        return $this->success(new UserResource($user), 'Data user berhasil diambil');
    }
}