<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;

class CustomRegister extends BaseRegister
{
    /**
     * IMPORTANTE: Definimos la propiedad $data para que Livewire tenga 
     * un lugar donde almacenar y validar los campos (name, lastname, etc.)
     */
    public ?array $data = [];

    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
{
    $user = parent::handleRegistration($data);
    $user->assignRole('Admin');
    return $user;
}
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(), 
                
                TextInput::make('lastname')
                    ->label('Apellido')
                    ->required(),
                    
                TextInput::make('cedula')
                    ->label('Cédula/ID')
                    ->required()
                    ->maxLength(8)
                    ->unique('users', 'cedula'),
                    
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->mask('9999-9999999')
                    ->required(),

                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ])
            ->statePath('data'); 
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'pending';
        
        return $data;
    }

    protected function afterRegister(): void
    {
        $user = auth()->user();
        
        if ($user) {
            $user->assignRole('Admin');
        }
    }

    protected function getRedirectUrl(): string
{
    return route('/admin'); 
}

    public function getHeading(): string
    {
        return 'Registro de Administrador';
    }
}