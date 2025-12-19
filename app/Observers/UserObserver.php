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
        if($user->status === 'approved') {
            return;
        }
        // Buscamos a los Superadmins
        $superadmins = User::whereHas('roles', fn($q) => $q->where('name', 'Superadmin'))->get();

        if ($superadmins->isNotEmpty()) {
            Notification::make()
                ->title('Nueva solicitud de Administrador')
                ->icon('heroicon-o-user-plus')
                ->iconColor('warning')
                ->warning() 
                ->body("El usuario {$user->name} {$user->lastname} se ha registrado y espera aprobación.")
                ->persistent() 
                ->viewData(['user_id' => $user->id])
                ->actions([
                    // Botón Aprobar (Verde)
                    NotificationAction::make('aprobar')
                        ->label('Aprobar')
                        ->button()
                        ->color('success')
                        ->url(fn () => route('usuario.aprobar', [
                            'user' => $user->id, 
                        ])),
                    // Botón Rechazar (Rojo)
                    NotificationAction::make('rechazar')
                        ->label('Rechazar')
                        ->link() 
                        ->color('danger')
                        ->url(fn () => route('usuario.rechazar', [
                            'user' => $user->id, 
                        ])),
                ])
                ->sendToDatabase($superadmins);
        }
    }
}