<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QueueTicket;
use App\Services\Antrian\QueueTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lets the Khanza cashier client call a specific queue ticket by no_rawat,
 * triggered from DlgKasirRalan's "Masuk Poli" action — the staff there
 * already picked which visit to call, so this bypasses the FIFO
 * QueueTicketService::callNext().
 */
class AntrianCallController extends Controller
{
    public function store(Request $request, QueueTicketService $queueTicketService): JsonResponse
    {
        $data = $request->validate([
            'no_rawat' => ['required', 'string'],
        ]);

        $ticket = QueueTicket::query()->where('no_rawat', $data['no_rawat'])->first();

        if (! $ticket) {
            return response()->json([
                'message' => 'Tiket antrean tidak ditemukan untuk no_rawat tersebut.',
            ], 404);
        }

        if ($ticket->status !== 'waiting') {
            return response()->json([
                'message' => 'Tiket sudah tidak berstatus menunggu, tidak dipanggil ulang.',
                'status' => $ticket->status,
            ]);
        }

        $queueTicketService->callTicket($ticket);

        return response()->json([
            'message' => 'Tiket berhasil dipanggil.',
            'queue_number' => $ticket->fresh()->queue_number,
            'status' => $ticket->fresh()->status,
        ]);
    }
}
