<?php

namespace App\Observers;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if (!Auth::check() || $user->status === 'pending') {
            
            // Buscamos a los Superadmins
            $superadmins = User::role('Superadmin')->get();

            if ($superadmins->count() > 0) {
                Notification::make()
                    ->title('Nueva solicitud de Administrador')
                    ->icon('heroicon-o-user-plus')
                    ->body("El usuario **{$user->name}** se ha registrado de forma independiente y espera aprobación.")
                    ->warning()
                    ->persistent()
                    ->actions([
                        Action::make('aprobar')
                            ->label('Aprobar')
                            ->button()
                            ->color('success')
                            ->action(function () use ($user) {
                                $user->update(['status' => 'waiting_business']);
                                
                                Notification::make()
                                    ->title('Usuario aprobado')
                                    ->success()
                                    ->send();
                            }),
                        Action::make('rechazar')
                            ->label('Rechazar')
                            ->link()
                            ->color('danger')
                            ->requiresConfirmation()
                            ->action(fn () => $user->delete()),
                    ])
                    ->sendToDatabase($superadmins);
            }
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
