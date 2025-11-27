<?php

namespace App\Services;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use \App\Services\VerificationService;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\RateLimiter;

class AuthService 
{
    protected $verification;

    public function __construct(EmailVerificationService $verification)
    {
        $this->verification = $verification;
    }

    public function registerUser($data)
{
    // إنشاء المستخدم
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'email_verified_at' => null,
    ]);
    $user->assignRole('citizen');  

    // إرسال كود التحقق
    $this->verification->sendCode($user);

    // إنشاء JWT Token
    try {
        $token = JWTAuth::fromUser($user);
    } catch (JWTException $e) {
        throw new \Exception("Could not create token");
    }

    return [
        'user' => $user,
        'token' => $token
    ];
}

    public function loginUser($data)
{
    $email = trim($data['email']);

     $password = $data['password'];

    $key = 'login-attempts:' . $email;
    $maxAttempts = 5;
    $decaySeconds = 15 * 60;

    // Rate Limiter
    if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
        $seconds = RateLimiter::availableIn($key);
        return [
            'status' => 'rate_limited',
            'seconds' => $seconds
        ];
    }

    // جلب المستخدم
    $user = User::where('email', $email)->first();

    if (!$user || !Hash::check($password, $user->password)) {
        RateLimiter::hit($key, $decaySeconds);

        return [
            'status' => 'invalid_credentials',
            'attempts_left' => $maxAttempts - RateLimiter::attempts($key)
        ];
    }

    // Clear rate limiter
    RateLimiter::clear($key);

    // إرسال كود التفعيل لو الايميل غير مفعل
    if (is_null($user->email_verified_at)||$user->status==0) {

        $token = JWTAuth::fromUser($user);

        $this->verification->sendCode($user);

        return [
            'status' => 'email_not_verified',
            'user' => $user,
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 600
        ];
    }

    // لو الايميل مفعل → اعمل login طبيعي
    try {
        $token = JWTAuth::fromUser($user);
    } catch (JWTException $e) {
        throw new \Exception("Could not create token");
    }

    return [
        'status' => 'success',
        'user' => $user,
        'token' => $token,
        'expires_in' => auth('api')->factory()->getTTL() * 600
    ];
}
/*
public function registerEmployee($data)
{
    // إنشاء المستخدم
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'email_verified_at' => now(),
        'status'=>1, // يمكن مباشرة تفعيل البريد للموظف
        'government_entity_id' => $data['government_entity_id'] ?? null,
    ]);

    $user->assignRole('employee');  // تعيين دور الموظف

    // إنشاء JWT Token
    try {
        $token = JWTAuth::fromUser($user);
    } catch (JWTException $e) {
        throw new \Exception("Could not create token");
    }

    return [
        'user' => $user,
        'token' => $token
    ];
}
    */

}
