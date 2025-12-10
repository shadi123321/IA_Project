<?php

namespace App\Notifications;

use DevKandil\NotiFire\Enums\MessagePriority;
use DevKandil\NotiFire\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintStatusUpdatedFcm extends Notification
{
    use Queueable;

    protected $complaint;

    public function __construct($complaint)
    {
        $this->complaint = $complaint;
    }

    public function via(object $notifiable): array
    {
        return ['fcm'];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create(
            'Complaint Status Updated',
            "Your complaint #{$this->complaint->reference_number} is now {$this->complaint->status}"
        )
            ->sound('default')
            ->icon('notification_icon')
            ->color('#0D6EFD')
            ->priority(MessagePriority::HIGH)
            ->data([
                'complaint_id'       => $this->complaint->id,
                'reference_number'   => $this->complaint->reference_number,
                'new_status'         => $this->complaint->status,
                'changed_at'         => now()->toIso8601String(),
            ]);
    }
}
