<?php

namespace App\Livewire\Patient;

use App\Models\PatientAccount;
use App\Models\QueueTicket;
use App\Services\Antrian\QueueTicketService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Polls the patient's own active queue tickets so position/status update
 * without a manual page refresh (see wire:poll in the component view).
 */
class QueueBoard extends Component
{
    public function render(QueueTicketService $queueTicketService): View
    {
        /** @var PatientAccount $account */
        $account = auth('pasien')->user();

        $tickets = QueueTicket::query()
            ->where('no_rkm_medis', $account->no_rkm_medis)
            ->whereIn('status', ['waiting', 'called'])
            ->orderByDesc('tanggal')
            ->orderBy('queue_number')
            ->get()
            ->map(fn (QueueTicket $ticket): array => [
                'ticket' => $ticket,
                'position_ahead' => $queueTicketService->positionAhead($ticket),
                'eta_minutes' => $queueTicketService->etaMinutes($ticket),
            ]);

        return view('livewire.patient.queue-board', [
            'tickets' => $tickets,
        ]);
    }
}
