<?php

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/espera-aprobacion', function () {
    return view('espera');
})->name('espera.aprobacion')->middleware('auth');

Route::get('/aprobar-usuario/{user}', function (User $user) {
    if (!auth()->user()?->hasRole('Superadmin')) {
        abort(403, 'No tienes permiso para realizar esta acción.');
    }

    $user->update(['status' => 'approved']); 

    Notification::make()
        ->title('¡Usuario Aprobado!')
        ->success()
        ->body("El usuario {$user->name} {$user->lastname} ahora tiene acceso.")
        ->send();

    return redirect('/admin/users');
})->name('usuario.aprobar')->middleware(['auth']);

Route::get('/rechazar-usuario/{user}', function (User $user) {
    if (!auth()->user()?->hasRole('Superadmin')) {
        abort(403, 'No tienes permiso para realizar esta acción.');
    }

    $user->delete();

    Notification::make()
        ->title('Usuario Rechazado')
        ->danger()
        ->body("La cuenta ha sido eliminada permanentemente.")
        ->send();

    return redirect('/admin/users');
})->name('usuario.rechazar')->middleware(['auth']);