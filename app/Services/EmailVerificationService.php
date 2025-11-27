<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Jobs\SendVerificationCodeJob;
 class EmailVerificationService
{
   public function sendCode(User $user, int $minutes = 5)
{
    $code = rand(111111, 999999);

    Cache::put('verify_'.$user->id, $code, now()->addMinutes($minutes));

    dispatch(new SendVerificationCodeJob($user, $code));

    return $code;
}

public function verifyCode(int $userId, int $code)
{
    $cachedCode = Cache::get('verify_'.$userId);

    if (!$cachedCode || $cachedCode != $code) {
        return false;
    }

    $user = User::findOrFail($userId);
    $user->status = 1;
    $user->email_verified_at = now();
    $user->save();

    Cache::forget('verify_'.$userId);

    return true;
}

}
