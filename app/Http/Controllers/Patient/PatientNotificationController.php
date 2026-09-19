<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Services\Patients\PatientNotificationFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientNotificationController extends Controller
{
    public function __construct(
        private readonly PatientNotificationFeed $feed,
    ) {}

    public function index(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        // Read the cutoff before marking read, so this visit can still
        // highlight what was new.
        $readAt = $account->notifications_read_at;
        $notifications = $this->feed->for($account);

        $this->feed->markAllRead($account);

        return view('patient.notifikasi', [
            'notifications' => $notifications,
            'readAt' => $readAt,
        ]);
    }
}
