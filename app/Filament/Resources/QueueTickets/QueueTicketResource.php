<?php

namespace App\Filament\Resources\QueueTickets;

use App\Filament\Resources\QueueTickets\Pages\ManageQueueTickets;
use App\Models\QueueTicket;
use App\Repositories\Khanza\DokterRepository;
use App\Repositories\Khanza\PasienRepository;
use App\Repositories\Khanza\PoliklinikRepository;
use App\Services\Antrian\QueueTicketService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The staff queue board. Deliberately has no create/edit/delete
 * UI — queue_tickets rows are only ever created by the antrian:sync
 * poller and transitioned by the actions below (see
 * App\Services\Antrian\QueueTicketService).
 */
class QueueTicketResource extends Resource
{
    protected static ?string $model = QueueTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Papan Antrian';

    protected static ?string $modelLabel = 'Antrean';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereDate('tanggal', today());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        $todaysTickets = static::getEloquentQuery()->get(['no_rkm_medis', 'kd_poli', 'kd_dokter']);

        $pasienByNoRkmMedis = app(PasienRepository::class)->findMany($todaysTickets->pluck('no_rkm_medis')->all());
        $poliByKdPoli = app(PoliklinikRepository::class)->findMany($todaysTickets->pluck('kd_poli')->all());
        $dokterByKdDokter = app(DokterRepository::class)->findMany($todaysTickets->pluck('kd_dokter')->all());

        return $table
            ->poll('5s')
            ->defaultSort('queue_number')
            ->columns([
                TextColumn::make('queue_number')
                    ->label('No')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('no_rkm_medis')
                    ->label('Pasien')
                    ->formatStateUsing(fn (string $state): string => $pasienByNoRkmMedis[$state]?->nm_pasien ?? $state)
                    ->searchable(),
                TextColumn::make('kd_poli')
                    ->label('Poli')
                    ->formatStateUsing(fn (string $state): string => $poliByKdPoli[$state]?->nm_poli ?? $state),
                TextColumn::make('kd_dokter')
                    ->label('Dokter')
                    ->formatStateUsing(fn (string $state): string => $dokterByKdDokter[$state]?->nm_dokter ?? $state),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'waiting' => 'Menunggu',
                        'called' => 'Dipanggil',
                        'serving' => 'Diperiksa',
                        'done' => 'Selesai',
                        'skipped' => 'Dilewati',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'gray',
                        'called' => 'warning',
                        'serving' => 'info',
                        'done' => 'success',
                        'skipped', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('called_at')
                    ->label('Dipanggil')
                    ->dateTime('H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('kd_poli')
                    ->label('Poli')
                    ->options(fn (PoliklinikRepository $poliklinikRepository): array => $poliklinikRepository->active()->pluck('nm_poli', 'kd_poli')->all()),
            ])
            ->recordActions([
                Action::make('call')
                    ->label('Panggil')
                    ->icon(Heroicon::OutlinedSpeakerWave)
                    ->authorize('update')
                    ->visible(fn (QueueTicket $record): bool => $record->status === 'waiting')
                    ->requiresConfirmation()
                    ->action(function (QueueTicket $record, QueueTicketService $queueTicketService): void {
                        $queueTicketService->callNext($record->kd_poli, $record->tanggal);

                        Notification::make()->success()->title('Nomor antrean dipanggil')->send();
                    }),
                Action::make('done')
                    ->label('Selesai')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (QueueTicket $record): bool => $record->status === 'called')
                    ->action(function (QueueTicket $record, QueueTicketService $queueTicketService): void {
                        $queueTicketService->markDone($record);
                    }),
                Action::make('skip')
                    ->label('Lewati')
                    ->icon(Heroicon::OutlinedForward)
                    ->color('danger')
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->visible(fn (QueueTicket $record): bool => in_array($record->status, ['waiting', 'called'], true))
                    ->action(function (QueueTicket $record, QueueTicketService $queueTicketService): void {
                        $queueTicketService->skip($record);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQueueTickets::route('/'),
        ];
    }
}
