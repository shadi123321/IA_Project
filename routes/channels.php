<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| هنا يمكنك تعريف القنوات الخاصة بك لتحديد من يمكنه الاستماع لكل قناة.
|
*/

Broadcast::channel('App.Models.User.{userId}', function ($user, $userId) {
    // المواطن يمكنه الاستماع فقط لقناته الخاصة
    return (int) $user->id === (int) $userId;
});
