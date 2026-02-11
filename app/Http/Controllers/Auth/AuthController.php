<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse as Json;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): Json
    {
        $user = new User();
        $user->first_name = $request->first_name;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->save();

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): Json
    {
        if (!Auth::attempt($request->validated())) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 200);
    }

    public function user(): Json
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    public function logout(): Json
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Logout successful'
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request): Json
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->password = $password;
                $user->remember_token = Str::random(60);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __('passwords.reset')]);
        }

        return response()->json(['message' => __("passwords.{$status}")], 400);
    }

    public function forgotPassword(ForgotPasswordRequest $request): Json
    {
        Password::sendResetLink($request->validated());

        return response()->json([
            'message' => __('passwords.sent'),
        ], 200);
    }
}
