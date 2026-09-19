<?php

namespace App\Jobs;

use App\Models\LabResultNotification;
use App\Services\Lab\LabResultNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sends one lab-result WhatsApp message, queued so the `lab:sync` poller
 * never blocks on the Gowa HTTP call.
 */
class SendLabResultNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $labResultNotificationId,
    ) {}

    public function handle(LabResultNotifier $notifier): void
    {
        $notification = LabResultNotification::query()->find($this->labResultNotificationId);

        if ($notification) {
            $notifier->notify($notification);
        }
    }
}
