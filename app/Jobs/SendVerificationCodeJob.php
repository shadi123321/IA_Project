<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendVerificationCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $code;
    protected $subject;

    public function __construct(User $user, $code)
    {
        $this->user = $user;
        $this->code = $code;
        $this->subject = "Flow Trip - Email Verification";
    }

    public function handle()
    {
        $emailBody = "Hello {$this->user->name}!\nYour verification code is: {$this->code}";

        Mail::raw($emailBody, function ($message) {
            $message->to($this->user->email)
                    ->subject($this->subject);
        });
    }
}
