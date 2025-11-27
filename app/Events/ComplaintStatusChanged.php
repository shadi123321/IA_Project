<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ComplaintStatusChanged implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $userId;   // المواطن الذي سيستقبل الإشعار
    public $complaintId;
    public $status;
    public $message;

    public function __construct($userId, $complaintId, $status, $message)
    {
        $this->userId = $userId;
        $this->complaintId = $complaintId;
        $this->status = $status;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('notifications.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'complaint-status-changed';
    }
}
