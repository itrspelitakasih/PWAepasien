<?php

use App\Http\Controllers\Patient\PatientAuthController;
use App\Http\Controllers\Patient\PatientHistoryController;
use App\Http\Controllers\Patient\PatientOtpController;
use App\Http\Controllers\Patient\PatientPortalController;
use App\Http\Controllers\Patient\PatientQueueController;
use App\Http\Controllers\Patient\PatientRegistrationController;
use App\Http\Controllers\Patient\PatientRegistrationRequestController;
use App\Http\Controllers\Patient\PatientScheduleController;
use App\Http\Controllers\Patient\PatientSuratController;
use App\Http\Middleware\EnsurePortalIsConfigured;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('patient.')->middleware(EnsurePortalIsConfigured::class)->group(function () {
    Route::middleware('guest:pasien')->group(function () {
        Route::get('welcome', [PatientPortalController::class, 'welcome'])->name('welcome');

        Route::get('login', [PatientAuthController::class, 'create'])->name('login');
        Route::post('login', [PatientAuthController::class, 'store'])->name('login.store');

        Route::get('login/otp', [PatientOtpController::class, 'create'])->name('otp.request');
        Route::post('login/otp', [PatientOtpController::class, 'store'])->name('otp.send');
        Route::get('login/otp/verify', [PatientOtpController::class, 'edit'])->name('otp.verify');
        Route::post('login/otp/verify', [PatientOtpController::class, 'update'])->name('otp.verify.store');

        Route::get('daftar', [PatientRegistrationRequestController::class, 'create'])->name('register');
        Route::post('daftar', [PatientRegistrationRequestController::class, 'store'])->name('register.store');
    });

    Route::middleware('auth:pasien')->group(function () {
        Route::post('logout', [PatientAuthController::class, 'destroy'])->name('logout');
        Route::get('/', [PatientPortalController::class, 'index'])->name('dashboard');
        Route::get('settings', [PatientPortalController::class, 'edit'])->name('settings');
        Route::put('settings/preferences', [PatientPortalController::class, 'updatePreferences'])->name('settings.preferences');
        Route::put('settings/theme', [PatientPortalController::class, 'updateTheme'])->name('settings.theme');
        Route::post('settings/wa-number', [PatientPortalController::class, 'requestWaNumberChange'])->name('settings.wa-number.request');
        Route::post('settings/wa-number/verify', [PatientPortalController::class, 'confirmWaNumberChange'])->name('settings.wa-number.verify');

        Route::get('antrian', [PatientQueueController::class, 'index'])->name('antrian');
        Route::get('jadwal', [PatientScheduleController::class, 'index'])->name('jadwal');
        Route::get('riwayat', [PatientHistoryController::class, 'index'])->name('riwayat');
        Route::get('riwayat/detail', [PatientHistoryController::class, 'show'])->name('riwayat.show');
        Route::get('surat', [PatientSuratController::class, 'index'])->name('surat');
        Route::get('pendaftaran', [PatientRegistrationController::class, 'create'])->name('pendaftaran');
        Route::post('pendaftaran', [PatientRegistrationController::class, 'store'])->name('pendaftaran.store');
    });
});
