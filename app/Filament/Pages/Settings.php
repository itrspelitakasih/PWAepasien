<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PDO;
use PDOException;
use Throwable;

class Settings extends Page implements HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $title = 'Pengaturan Aplikasi';

    /**
     * Preset theme colors (hex => label). The hex is the `600` shade.
     *
     * @var array<string, string>
     */
    private const THEME_PRESETS = [
        '#2563eb' => 'Biru',
        '#0d9488' => 'Teal',
        '#059669' => 'Hijau',
        '#4f46e5' => 'Indigo',
        '#7c3aed' => 'Ungu',
        '#e11d48' => 'Merah',
        '#ea580c' => 'Oranye',
    ];

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::current()->only([
            'app_name', 'logo_path', 'icon_path', 'favicon_path', 'theme_color',
            'gowa_base_url', 'gowa_username', 'gowa_password', 'gowa_timeout', 'gowa_default_device_id',
            'sik_db_host', 'sik_db_port', 'sik_db_database', 'sik_db_username', 'sik_db_password',
            'radiologi_image_base_url',
            'queue_purge_enabled', 'queue_purge_keep_days',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Aplikasi')
                    ->description('Nama aplikasi yang ditampilkan di seluruh portal dan panel admin.')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Nama Aplikasi')
                            ->required()
                            ->maxLength(255),
                    ]),
                Section::make('Logo & Ikon')
                    ->description('Logo tampil di sidebar panel. Ikon digunakan sebagai ikon aplikasi (mis. apple-touch-icon). Favicon tampil di tab browser.')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('settings')
                            ->imageEditor(),
                        FileUpload::make('icon_path')
                            ->label('Icon')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('settings')
                            ->imageEditor(),
                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('settings')
                            ->imageEditor(),
                    ])
                    ->columns(3),
                Section::make('Tema Warna Portal')
                    ->description('Warna utama portal pasien (tombol, tautan, menu aktif, dan header). Kosongkan untuk memakai biru bawaan. Pilih warna yang cukup gelap agar teks putih tetap terbaca.')
                    ->schema([
                        ToggleButtons::make('theme_preset')
                            ->label('Pilihan Cepat')
                            ->options(self::THEME_PRESETS)
                            ->inline()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateHydrated(fn (ToggleButtons $component, callable $get) => $component->state(
                                array_key_exists((string) $get('theme_color'), self::THEME_PRESETS) ? $get('theme_color') : null,
                            ))
                            ->afterStateUpdated(fn ($state, callable $set) => $set('theme_color', $state)),
                        ColorPicker::make('theme_color')
                            ->label('Warna Utama')
                            ->helperText('Kosongkan untuk kembali ke warna bawaan.')
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set(
                                'theme_preset',
                                array_key_exists((string) $state, self::THEME_PRESETS) ? $state : null,
                            )),
                    ]),
                Section::make('WhatsApp (GOWA)')
                    ->description('Koneksi ke server GOWA untuk mengirim OTP login dan notifikasi antrean via WhatsApp.')
                    ->schema([
                        TextInput::make('gowa_base_url')
                            ->label('Base URL Server GOWA')
                            ->url()
                            ->placeholder(config('gowa.base_url'))
                            ->maxLength(255),
                        TextInput::make('gowa_username')
                            ->label('Username')
                            ->placeholder(config('gowa.username'))
                            ->maxLength(255),
                        TextInput::make('gowa_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        TextInput::make('gowa_timeout')
                            ->label('Timeout (detik)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->placeholder((string) config('gowa.timeout')),
                        TextInput::make('gowa_default_device_id')
                            ->label('Default Device ID')
                            ->helperText('Kosongkan untuk memakai instance pertama yang terhubung.')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->footerActions([
                        Action::make('testGowaConnection')
                            ->label('Uji Koneksi WhatsApp')
                            ->icon(Heroicon::OutlinedSignal)
                            ->color('gray')
                            ->action(fn () => $this->testGowaConnection()),
                    ]),
                Section::make('Koneksi Database SIMRS Khanza')
                    ->description('Koneksi baca-saja (read-only) ke database SIMRS Khanza, dipakai untuk data pasien, dokter, jadwal, dan registrasi.')
                    ->schema([
                        TextInput::make('sik_db_host')
                            ->label('Host')
                            ->placeholder(config('database.connections.sik.host'))
                            ->maxLength(255),
                        TextInput::make('sik_db_port')
                            ->label('Port')
                            ->numeric()
                            ->placeholder((string) config('database.connections.sik.port')),
                        TextInput::make('sik_db_database')
                            ->label('Nama Database')
                            ->placeholder(config('database.connections.sik.database'))
                            ->maxLength(255),
                        TextInput::make('sik_db_username')
                            ->label('Username')
                            ->placeholder(config('database.connections.sik.username'))
                            ->maxLength(255),
                        TextInput::make('sik_db_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->footerActions([
                        Action::make('testSikConnection')
                            ->label('Uji Koneksi Database')
                            ->icon(Heroicon::OutlinedSignal)
                            ->color('gray')
                            ->action(fn () => $this->testSikConnection()),
                    ]),
                Section::make('Gambar Radiologi')
                    ->description('Lokasi gambar hasil pemeriksaan radiologi dari SIMRS Khanza. Gambar ditampilkan pada riwayat radiologi pasien.')
                    ->schema([
                        TextInput::make('radiologi_image_base_url')
                            ->label('URL Folder Gambar Radiologi')
                            ->url()
                            ->placeholder('http://192.168.1.10/webapps/radiologi')
                            ->helperText('Alamat folder webapps Khanza yang menyimpan gambar radiologi. Ditambahkan di depan path pada kolom lokasi_gambar (tabel gambar_radiologi). Kosongkan untuk tidak menampilkan gambar.')
                            ->maxLength(255),
                    ]),
                Section::make('Pembersihan Tiket Antrean')
                    ->description('Tiket antrean pada tanggal yang sudah lewat tidak dipakai lagi oleh portal. Aktifkan agar tiket tersebut dihapus otomatis setiap dini hari (pukul 00:05). Membutuhkan penjadwal (schedule:work atau cron) yang berjalan.')
                    ->schema([
                        Toggle::make('queue_purge_enabled')
                            ->label('Hapus tiket antrean lama secara otomatis')
                            ->live(),
                        TextInput::make('queue_purge_keep_days')
                            ->label('Simpan riwayat (hari)')
                            ->helperText('0 = hanya tiket hari ini yang disimpan. 7 = tiket 7 hari terakhir juga disimpan.')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(365)
                            ->default(0)
                            ->required()
                            ->visible(fn (callable $get): bool => (bool) $get('queue_purge_enabled')),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->key('form-actions'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::current()->update($data);
        Cache::forget('portal.setup_ready');

        Notification::make()
            ->success()
            ->title('Pengaturan berhasil disimpan')
            ->send();

        $this->mount();
    }

    public function testGowaConnection(): void
    {
        $state = $this->form->getState();

        $baseUrl = rtrim((string) ($state['gowa_base_url'] ?: config('gowa.base_url')), '/');
        $username = (string) ($state['gowa_username'] ?: config('gowa.username'));
        $password = (string) ($state['gowa_password'] ?: config('gowa.password'));
        $timeout = (int) ($state['gowa_timeout'] ?: config('gowa.timeout', 15));

        if ($baseUrl === '' || $username === '' || $password === '') {
            Notification::make()->danger()->title('Isi Base URL, username, dan password terlebih dahulu')->send();

            return;
        }

        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout($timeout)
                ->acceptJson()
                ->get("{$baseUrl}/devices");

            if ($response->successful()) {
                Notification::make()->success()->title('Koneksi WhatsApp berhasil')->send();

                return;
            }

            Notification::make()->danger()->title("Koneksi gagal: HTTP {$response->status()}")->send();
        } catch (Throwable $exception) {
            Notification::make()->danger()->title('Koneksi gagal')->body($exception->getMessage())->send();
        }
    }

    public function testSikConnection(): void
    {
        $state = $this->form->getState();

        $host = (string) ($state['sik_db_host'] ?: config('database.connections.sik.host'));
        $port = (string) ($state['sik_db_port'] ?: config('database.connections.sik.port'));
        $database = (string) ($state['sik_db_database'] ?: config('database.connections.sik.database'));
        $username = (string) ($state['sik_db_username'] ?: config('database.connections.sik.username'));
        $password = (string) ($state['sik_db_password'] ?: config('database.connections.sik.password'));

        try {
            new PDO(
                "mysql:host={$host};port={$port};dbname={$database}",
                $username,
                $password,
                [PDO::ATTR_TIMEOUT => 5],
            );

            Notification::make()->success()->title('Koneksi database SIMRS berhasil')->send();
        } catch (PDOException $exception) {
            Notification::make()->danger()->title('Koneksi database SIMRS gagal')->body($exception->getMessage())->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
