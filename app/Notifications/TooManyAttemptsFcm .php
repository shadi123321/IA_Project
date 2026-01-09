<?php

namespace App\Notifications;

use DevKandil\NotiFire\Enums\MessagePriority;
use DevKandil\NotiFire\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TooManyAttemptsFcm extends Notification
{
    use Queueable;

    protected $secondsRemaining;

    public function __construct($secondsRemaining)
    {
        $this->secondsRemaining = $secondsRemaining;
    }

    public function via(object $notifiable): array
    {
        if ($notifiable->fcm_token) {
            return ['fcm'];
        }

        return [];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $minutes = ceil($this->secondsRemaining / 60);

        return FcmMessage::create(
            'Too Many Attempts',
            "You've made too many attempts. Please try again in {$minutes} minute(s)."
        )
            ->sound('default')
            ->icon('notification_icon')
            ->color('#FF6B6B')
            ->priority(MessagePriority::HIGH)
            ->data([
                'type' => 'rate_limit',
                'seconds_remaining' => $this->secondsRemaining,
                'retry_after' => now()->addSeconds($this->secondsRemaining)->toIso8601String(),
            ]);
    }
}
