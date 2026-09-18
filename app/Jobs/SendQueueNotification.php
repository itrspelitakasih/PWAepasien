<?php

namespace App\Jobs;

use App\Models\QueueTicket;
use App\Services\Antrian\QueueNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

/**
 * Sends one queue WhatsApp notification for one ticket, queued so
 * QueueTicketService's transitions (and the antrian:sync poller) never
 * block on the Gowa HTTP call.
 */
class SendQueueNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  'issued'|'near'|'called'  $type
     */
    public function __construct(
        public readonly int $queueTicketId,
        public readonly string $type,
    ) {}

    public function handle(QueueNotifier $notifier): void
    {
        $ticket = QueueTicket::query()->find($this->queueTicketId);

        if (! $ticket) {
            return;
        }

        match ($this->type) {
            'issued' => $notifier->notifyIssued($ticket),
            'near' => $notifier->notifyNear($ticket),
            'called' => $notifier->notifyCalled($ticket),
            default => throw new InvalidArgumentException("Unknown queue notification type [{$this->type}]."),
        };
    }
}
