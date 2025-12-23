<?php

namespace App\Filament\Resources\UsersResource\Pages;

use App\Filament\Resources\UsersResource;
use Filament\Resources\Pages\EditRecord;

class EditUsers extends EditRecord
{
    protected static string $resource = UsersResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Un admin NO puede convertir a nadie en Superadmin
        if (auth()->user()->hasRole('Admin') && $data['roles'] === 'Superadmin') {
            unset($data['roles']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
