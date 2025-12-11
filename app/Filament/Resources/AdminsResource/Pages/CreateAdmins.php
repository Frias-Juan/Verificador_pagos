<?php

namespace App\Filament\Resources\AdminsResource\Pages;

use App\Filament\Resources\AdminsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmins extends CreateRecord
{
    protected static string $resource = AdminsResource::class;
    protected static ?string $title = 'Registrar un negocio';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
