<?php

namespace App\Filament\Resources\QueueTickets\Pages;

use App\Filament\Resources\QueueTickets\QueueTicketResource;
use Filament\Resources\Pages\ManageRecords;

class ManageQueueTickets extends ManageRecords
{
    protected static string $resource = QueueTicketResource::class;
}
