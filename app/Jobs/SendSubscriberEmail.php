<?php

namespace App\Jobs;

use App\Models\Tenant\Subscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSubscriberEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $to,
        public string $subject,
        public string $content
    ) {}

    public function handle(): void
    {
        Mail::raw($this->content, function ($message) {
            $message->to($this->to)
                ->subject($this->subject);
        });
    }
}
