<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\RuntimeSettingsServiceProvider;

return [
    RuntimeSettingsServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
];
