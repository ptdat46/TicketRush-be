<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerRegisterRequest;
use App\Http\Requests\Auth\OrganizerRegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Http\Requests\Auth\ResendCodeRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly VerificationCodeService $verificationCodeService
    ) {}

    /**
     * Register a new customer account.
     */
    public function registerCustomer(CustomerRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'gender' => $data['gender'] ?? null,
            'birthday' => $data['birthday'] ?? null,
        ]);

        $this->verificationCodeService->sendCode($user->email, 'register');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email for the verification code.',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Register a new organizer account.
     */
    public function registerOrganizer(OrganizerRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'organizer',
            'organizer_name' => $data['organizer_name'],
            'tax_code' => $data['tax_code'],
        ]);

        $this->verificationCodeService->sendCode($user->email, 'register');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email for the verification code.',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Verify email with the 6-digit code and return Sanctum token.
     */
    public function verifyEmail(VerifyCodeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $verified = $this->verificationCodeService->verifyCode($data['email'], $data['code'], 'register');

        if (! $verified) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->email_verified_at = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Resend the verification code.
     */
    public function resendCode(ResendCodeRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        $this->verificationCodeService->sendCode($email, 'register');

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.',
        ]);
    }

    /**
     * Login and return Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if (is_null($user->email_verified_at)) {
            $this->verificationCodeService->sendCode($user->email, 'register');

            return response()->json([
                'success' => false,
                'message' => 'Email not verified. A new verification code has been sent to your email.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Logout and revoke current token.
     */
    public function logout(): JsonResponse
    {
        $request = request();
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(): JsonResponse
    {
        $user = request()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'gender' => $user->gender,
                'birthday' => $user->birthday?->toDateString(),
                'organizer_name' => $user->organizer_name,
                'tax_code' => $user->tax_code,
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Update authenticated user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'organizer_name' => $user->organizer_name,
                'tax_code' => $user->tax_code,
            ],
        ]);
    }
}
