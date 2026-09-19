<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Models\QueueTicket;
use App\Repositories\Khanza\PoliklinikRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PatientQueueController extends Controller
{
    public function __construct(
        private readonly PoliklinikRepository $poliklinikRepository,
    ) {}

    public function index(): View
    {
        return view('patient.antrian');
    }

    /**
     * Today's tickets currently being called for this patient. Polled by the
     * portal layout so the phone can vibrate the moment a ticket is called.
     */
    public function called(Request $request): JsonResponse
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        $tickets = QueueTicket::query()
            ->where('no_rkm_medis', $account->no_rkm_medis)
            ->where('status', 'called')
            ->whereDate('tanggal', Carbon::today())
            ->get()
            ->map(fn (QueueTicket $ticket): array => [
                'id' => $ticket->id,
                'queue_number' => $ticket->queue_number,
                'poli' => $this->poliklinikRepository->find($ticket->kd_poli)?->nm_poli ?? $ticket->kd_poli,
            ]);

        return response()->json(['called' => $tickets->values()]);
    }
}
