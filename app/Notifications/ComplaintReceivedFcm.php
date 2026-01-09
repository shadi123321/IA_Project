<?php

namespace App\Notifications;

use DevKandil\NotiFire\Enums\MessagePriority;
use DevKandil\NotiFire\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintReceivedFcm extends Notification
{
    use Queueable;

    protected $complaint;

    public function __construct($complaint)
    {
        $this->complaint = $complaint;
    }

    public function via(object $notifiable): array
    {
        // Only send via FCM if user has a token
        if ($notifiable->fcm_token) {
            return ['fcm'];
        }

        return []; // Return empty array if no token
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create(
            'Complaint Received Successfully',
            "Your complaint #{$this->complaint->reference_number} has been submitted and is under review"
        )
            ->sound('default')
            ->icon('notification_icon')
            ->color('#0D6EFD')
            ->priority(MessagePriority::HIGH)
            ->data([
                'complaint_id'       => $this->complaint->complaint_id,
                'reference_number'   => $this->complaint->reference_number,
                'status'             => $this->complaint->status,
                'government_entity'  => $this->complaint->governmentEntity->name ?? 'Government Entity',
                'submitted_at'       => $this->complaint->created_at->toIso8601String(),
                'type'               => 'complaint_received',
            ]);
    }
}
