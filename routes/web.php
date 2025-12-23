<?php

use App\Models\User;
use Filament\Notifications\DatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/impersonate/leave', function () {
    $manager = app(ImpersonateManager::class);

    if ($manager->isImpersonating()) {
        // 1. Salir de la suplantación
        $manager->leave();

        // 2. LIMPIAR HASHES DE SESIÓN
        // Esto es crucial para que no te mande al login al volver a ser Superadmin
        session()->forget([
            'password_hash_web',
            'password_hash_filament',
            'password_hash_sanctum',
        ]);

        // 3. Redirigir al panel de Superadmin
        return redirect()->to('/admin'); 
    }

    return redirect()->to('/admin/login');
})->name('impersonate.leave')->middleware('web');

Route::get('/espera-aprobacion', function () {
    return view('espera');
})->name('espera.aprobacion')->middleware('auth');

Route::get('/aprobar-usuario/{user}', function (User $user) {
    if (!auth()->user()?->hasRole('Superadmin')) {
        abort(403, 'No tienes permiso para realizar esta acción.');
    }

    DB::table('notifications')
        ->where('data', 'like', '%"user_id":' . $user->id . '%')
        ->delete();

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

   DB::table('notifications')
        ->where('data', 'like', '%"user_id":' . $user->id . '%')
        ->delete();
    $user->delete();

    Notification::make()
        ->title('Usuario Rechazado')
        ->danger()
        ->body("La cuenta ha sido eliminada permanentemente.")
        ->send();

    return redirect('/admin/users');
})->name('usuario.rechazar')->middleware(['auth']);