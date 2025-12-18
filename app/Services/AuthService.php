<?php

namespace App\Services;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    protected EmailVerificationService $verification;

    public function __construct(EmailVerificationService $verification)
    {
        $this->verification = $verification;
    }

    /* =========================
       Register Citizen
    ========================== */
    public function registerUser(array $data): array
    {
        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'email_verified_at' => null,
        ]);

        $user->assignRole('citizen');

        // Send verification code
        $this->verification->sendCode($user);

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            throw new \Exception('Could not create token');
        }

        return [
            'user'  => $user,
            'token' => $token
        ];
    }

    /* =========================
       Login User
    ========================== */
    public function loginUser(array $data): array
    {
        $email    = trim($data['email']);
        $password = $data['password'];

        $rateKey       = 'login-attempts:' . $email;
        $maxAttempts   = 5;
        $decaySeconds  = 15 * 60;

        /* ---------- Rate Limiter ---------- */
        if (RateLimiter::tooManyAttempts($rateKey, $maxAttempts)) {
            return [
                'status'  => 'rate_limited',
                'seconds' => RateLimiter::availableIn($rateKey)
            ];
        }

        /* ---------- Cache User ---------- */
        $cacheKey = "auth:user:{$email}";

        $user = Cache::remember($cacheKey, 300, function () use ($email) {
            return User::with('governmentEntity')
                ->where('email', $email)
                ->select(
                    'id',
                    'name',
                    'email',
                    'password',
                    'email_verified_at',
                    'status',
                    'government_entity_id'
                )
                ->first();
        });

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($rateKey, $decaySeconds);

            return [
                'status'         => 'invalid_credentials',
                'attempts_left'  => max(0, $maxAttempts - RateLimiter::attempts($rateKey))
            ];
        }

        RateLimiter::clear($rateKey);

       // إرسال كود التفعيل لو الايميل غير مفعل
    if (is_null($user->email_verified_at)/*||$user->status==0*/) {

        $token = JWTAuth::fromUser($user);

      //  $this->verification->sendCode($user);
        
          $user->email_verified_at = now();
     $user->save();
        
            try {
                $token = JWTAuth::fromUser($user);
            } catch (JWTException $e) {
                throw new \Exception('Could not create token');
            }

            return [
                'status'            => 'email_not_verified',
                'user'              => $user,
                'government_entity' => $user->governmentEntity,
                'token'             => $token,
                'expires_in'        => auth('api')->factory()->getTTL() * 600
            ];
        }

        /* ---------- Normal Login ---------- */
        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            throw new \Exception('Could not create token');
        }

        return [
            'status'            => 'success',
            'user'              => $user,
            'government_entity' => $user->governmentEntity,
            'token'             => $token,
            'expires_in'        => auth('api')->factory()->getTTL() * 600
        ];
    }
}
