<?php

namespace App\Observers;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public bool $afterCommit = true;

    public function created(User $user): void
    {
        // Buscamos a los Superadmins
        $superadmins = User::whereHas('roles', fn($q) => $q->where('name', 'Superadmin'))->get();

        if ($superadmins->isNotEmpty()) {
            Notification::make()
                ->title('Nueva solicitud de Administrador')
                ->icon('heroicon-o-user-plus')
                ->iconColor('warning')
                ->warning() // Esto le da el color de énfasis a la notificación
                ->body("El usuario {$user->name} {$user->lastname} se ha registrado y espera aprobación.")
                ->persistent() // Para que no desaparezca sola
                ->actions([
                    // Botón Aprobar (Verde)
                    NotificationAction::make('aprobar')
                        ->label('Aprobar')
                        ->button()
                        ->color('success')
                        ->url(route('usuario.aprobar', ['user' => $user->id])),

                    // Botón Rechazar (Rojo)
                    NotificationAction::make('rechazar')
                        ->label('Rechazar')
                        ->link() // Lo ponemos como link para que no compita visualmente con el botón principal
                        ->color('danger')
                        ->url(route('usuario.rechazar', ['user' => $user->id])),
                ])
                ->sendToDatabase($superadmins);
        }
    }
}