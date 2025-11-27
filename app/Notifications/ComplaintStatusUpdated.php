<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ComplaintStatusUpdated extends Notification
{
    use Queueable;

    public $complaint;

    public function __construct($complaint)
    {
        $this->complaint = $complaint;
    }

    // القنوات: قاعدة البيانات + البث عبر Pusher
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    // الإشعار في جدول notifications
    public function toDatabase($notifiable)
    {
        return [
            'complaint_id' => $this->complaint->id,
            'status'       => $this->complaint->status,
            'message'      => "Your complaint #{$this->complaint->reference_number} status changed."
        ];
    }

    // الإشعار عبر Pusher real-time
   public function toBroadcast($notifiable)
{
    return new BroadcastMessage([
        'complaint_id' => $this->complaint->id,
        'status'       => $this->complaint->status,
        'message'      => "Your complaint #{$this->complaint->reference_number} status changed."
    ]);
}

}
