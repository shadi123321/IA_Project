<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\authRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\VerificationService;
 use     App\Services\EmailVerificationService;
  use App\Services\AuthService;
  use App\Http\Requests\RegisterRequest;
 use App\Http\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    protected $verification;
    protected $auth;
     public function __construct(EmailVerificationService $verification,AuthService $auth)
    {
        $this->verification = $verification;
        $this->auth=$auth;
    }

    /**
     * User Registration + Send Verification Code
     */
public function register(RegisterRequest $request)
{
    $result = $this->auth->registerUser($request->validated());

    return response()->json([
        'status' => 1,
        'message' => 'User registered successfully. Verification code sent to email.',
        'token' => $result['token'],
        'user_id' => $result['user']->id
    ], 201);
}

public function login(LoginRequest $request)
{
    $result = $this->auth->loginUser($request->validated());

    switch ($result['status']) {

        case 'rate_limited':
            return response()->json([
                'message' => 'Too many login attempts. Try again in ' . $result['seconds'] . ' seconds.',
                'retry_after' => $result['seconds']
            ], 429);

        case 'invalid_credentials':
            return response()->json([
                'message' => 'Invalid credentials.',
                'attempts_left' => $result['attempts_left'],
            ], 401);

        case 'email_not_verified':
            return response()->json([
                'message' => 'Email not verified. Verification code sent.',
                'user_id' => $result['user']->id,
                'token' => $result['token'],
                'expires_in' => $result['expires_in'],
            ], 403);

        case 'success':
            return response()->json([
                'message' => 'Login successful.',
                'token' => $result['token'],
                'expires_in' => $result['expires_in'],
                'user_id' => $result['user']->id,
            ], 200);

        default:
            return response()->json([
                'message' => 'Unknown error.',
            ], 500);
    }
}


    /**
     * Email Verification: User enters the code
     */
   public function verifyEmail(Request $request, $id)
{
    $request->validate([
        'code' => 'required|integer',
    ]);

    // استخدم $id من الـ URL بدل $request->user_id
    $success = $this->verification->verifyCode($id, $request->code);

    if (!$success) {
        return response()->json([
            'status' => 0,
            'message' => 'Invalid or expired verification code.'
        ], 400);
    }

    return response()->json([
        'status' => 1,
        'message' => 'Email verified successfully.'
    ]);
}


    /**
     * Resend verification code
     */
    public function resendCode( $id)
    {

        $user = User::findOrFail($id);
if ($user->email_verified_at !== null) {
    return response()->json(['message' => 'Email already verified.'], 400);
}
        $this->verification->sendCode($user);

        return response()->json(['message' => 'Verification code resent.']);
    }

    /**
     * Logout user
     */
   public function logout()
{
    try {
        $user = Auth::user();
        JWTAuth::invalidate(JWTAuth::getToken());

        // إذا أردت تعطيل الحساب عند logout، فقط ضع email_verified_at = null
        if ($user) {
            $user->email_verified_at = null;
            $user->save();
        }
    } catch (JWTException $e) {
        return response()->json(['error' => 'Failed to logout, please try again'], 500);
    }

    return response()->json(['message' => 'Successfully logged out']);
}


    /**
     * Refresh token
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::parseToken()->refresh();

            return response()->json([
                'token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired, please login again'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }
    public function getUser()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to fetch user profile'], 500);
        }
    }
    public function updateUser(Request $request)
    {
        try {
            $user = Auth::user();
            $user->update($request->only(['name', 'email']));
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to update user'], 500);
        }
    }
}
