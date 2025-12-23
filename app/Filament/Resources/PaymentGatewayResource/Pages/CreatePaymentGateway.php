<?php

namespace App\Filament\Resources\PaymentGatewayResource\Pages;

use App\Filament\Resources\PaymentGatewayResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePaymentGateway extends CreateRecord
{
    protected static string $resource = PaymentGatewayResource::class;
    
    public function getTitle(): string 
    {
        return 'Registrar Pasarela de Pago';
    }

    // Cambia el texto del botón "Crear" al final del formulario
    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Registrar Pasarela');
    }

    // Cambia el texto del botón "Crear y crear otro"
    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Registrar y crear otra');
    }

    public function getBreadcrumbs(): array
    {
        return [
            // Enlace a la lista de pasarelas
            PaymentGatewayResource::getUrl('index') => 'Pasarelas de Pago',
            // El texto final que ves arriba a la derecha
            null => 'Registrar', 
        ];
    }

    protected function afterCreate(): void
    {
        $user = Auth::user();

        // Si el usuario es un Admin (no Superadmin), vinculamos automáticamente la pasarela
        if ($user->hasRole('Admin') && $user->tenant_id) {
            $this->record->tenants()->attach($user->tenant_id);
        }
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
