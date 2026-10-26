<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceLoanController;
use App\Http\Controllers\DeviceReservationController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LoanCheckoutController;
use App\Http\Controllers\LoanReturnController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\RoomBookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Öffentlich
|--------------------------------------------------------------------------
*/
Route::get('/impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::post('/locale', [ProfileController::class, 'locale'])->name('locale.switch');

// App-Installation (PWA): Browser laden Manifest und Icons ohne Cookies
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/pwa/icon-{size}.png', [PwaController::class, 'icon'])->whereIn('size', PwaController::ICON_SIZES)->name('pwa.icon');
Route::get('/pwa/icon-{size}-maskable.png', [PwaController::class, 'maskableIcon'])->whereIn('size', PwaController::ICON_SIZES)->name('pwa.icon.maskable');

/*
|--------------------------------------------------------------------------
| Angemeldet
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/', DashboardController::class)->name('home');
    Route::get('/search', SearchController::class)->middleware(['not-kiosk', 'throttle:search'])->name('search');

    // Kiosk verlassen: nur mit dem Passwort des Kiosk-Kontos
    Route::get('/kiosk/exit', [KioskController::class, 'show'])->name('kiosk.exit');
    Route::post('/kiosk/exit', [KioskController::class, 'logout'])->middleware('throttle:6,1');

    // Inventar-ID / Scanner
    Route::get('/scan', [LookupController::class, 'scan'])->name('scan');
    Route::post('/lookup', [LookupController::class, 'lookup'])->middleware('throttle:search')->name('lookup');
    Route::get('/d/{code}', [LookupController::class, 'show'])->where('code', '[^/]{1,64}')->name('lookup.show');

    // Geräte
    Route::middleware('can:devices.manage')->group(function () {
        Route::get('/devices/create', [DeviceController::class, 'create'])->name('devices.create');
        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::get('/devices/{device}/edit', [DeviceController::class, 'edit'])->name('devices.edit');
        Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
    });
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
    Route::get('/devices/{device}/qr/{unit?}', [LabelController::class, 'qr'])->whereNumber('unit')->name('devices.qr');

    // Ausleihen
    Route::get('/loans', [DeviceLoanController::class, 'index'])->name('loans.index');

    // Ausleihe per Scan – auch mehrere Geräte/Exemplare mit einer Eingabe
    Route::middleware('can:loans.create')->group(function () {
        Route::get('/loans/checkout', [LoanCheckoutController::class, 'create'])->name('loans.checkout');
        Route::post('/loans/checkout', [LoanCheckoutController::class, 'store'])->name('loans.checkout.store');
    });

    // Rückgabe per Scan (am Kiosk der einzige Weg, siehe ReturnScans)
    Route::middleware('can:loans.return')->group(function () {
        Route::get('/loans/return', [LoanReturnController::class, 'create'])->name('loans.return-scan');
        Route::post('/loans/return', [LoanReturnController::class, 'store'])->name('loans.return-scan.store');
        Route::post('/loans/return/scan', [LoanReturnController::class, 'scan'])->middleware('throttle:search')->name('loans.return-scan.lookup');
    });

    Route::get('/loans/history', [DeviceLoanController::class, 'history'])->middleware('not-kiosk')->name('loans.history');
    Route::post('/devices/{device}/loans', [DeviceLoanController::class, 'store'])->middleware('can:loans.create')->name('loans.store');
    Route::post('/loans/{loan}/return', [DeviceLoanController::class, 'return'])->middleware('can:loans.return')->name('loans.return');
    Route::post('/loans/{loan}/extend', [DeviceLoanController::class, 'extend'])->middleware('can:loans.extend')->name('loans.extend');
    Route::post('/loans/{loan}/resolve', [DeviceLoanController::class, 'resolveIssues'])->middleware('can:loans.resolve')->name('loans.resolve');

    // Vormerkungen
    Route::middleware('can:reservations.create')->group(function () {
        Route::get('/devices/{device}/reserve', [DeviceReservationController::class, 'create'])->name('reservations.create');
        Route::post('/devices/{device}/reserve', [DeviceReservationController::class, 'store'])->name('reservations.store');
    });
    Route::delete('/reservations/{reservation}', [DeviceReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::post('/reservations/{reservation}/convert', [DeviceReservationController::class, 'convert'])->name('reservations.convert');

    // Kategorien
    Route::resource('categories', CategoryController::class)->except(['show'])->middleware('can:categories.manage');

    // Räume & Raumbuchungen
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::middleware('can:rooms.manage')->group(function () {
        Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    });
    Route::middleware('can:rooms.book')->group(function () {
        Route::get('/rooms/{room}/book', [RoomBookingController::class, 'create'])->name('bookings.create');
        Route::post('/rooms/{room}/book', [RoomBookingController::class, 'store'])->name('bookings.store');
    });
    Route::get('/bookings', [RoomBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/archive', [RoomBookingController::class, 'archived'])->middleware('not-kiosk')->name('bookings.archived');
    Route::get('/bookings/{booking}/edit', [RoomBookingController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{booking}', [RoomBookingController::class, 'update'])->name('bookings.update');
    Route::delete('/bookings/{booking}', [RoomBookingController::class, 'destroy'])->name('bookings.destroy');

    // Kalender
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    // Etiketten
    Route::middleware('can:labels.print')->group(function () {
        Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
        Route::get('/labels/print', [LabelController::class, 'print'])->name('labels.print');
    });

    // Profil & Sitzungen (nicht am Kiosk: dort könnte jeder Name, Passwort oder Sitzungen ändern)
    Route::post('/profile/theme', [ProfileController::class, 'theme'])->name('profile.theme');
    Route::middleware('not-kiosk')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('password.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/profile/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::delete('/profile/sessions/others', [SessionController::class, 'destroyOthers'])->middleware('throttle:6,1')->name('sessions.destroy-others');
        Route::delete('/profile/sessions/{id}', [SessionController::class, 'destroy'])->where('id', '[0-9a-f]{32}')->name('sessions.destroy');
    });

    // Verwaltung – Nutzer & Einstellungen verlangen das Passwort erneut (gilt 3 Stunden)
    Route::prefix('admin')->name('admin.')->middleware('not-kiosk')->group(function () {
        Route::middleware(['can:users.manage', 'password.confirm'])->group(function () {
            Route::resource('users', UserController::class)->except(['show']);
            Route::delete('/users/{user}/sessions', [SessionController::class, 'destroyForUser'])->name('users.sessions.destroy');
        });

        Route::middleware('can:audit.view')->group(function () {
            Route::get('/audit', [AuditLogController::class, 'index'])->name('audit');
            Route::get('/audit/export', [AuditLogController::class, 'export'])->name('audit.export');
        });

        Route::middleware(['can:settings.manage', 'password.confirm'])->group(function () {
            Route::post('/settings/backups/run', [SettingsController::class, 'runBackup'])->middleware('throttle:3,10')->name('settings.backup');
            Route::get('/settings/{section?}', [SettingsController::class, 'edit'])->name('settings');
            Route::put('/settings/{section}', [SettingsController::class, 'update'])->name('settings.update');
        });
    });
});

require __DIR__.'/auth.php';
