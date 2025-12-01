<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsersResource\Pages;
use App\Filament\Resources\UsersResource\RelationManagers;
use App\Models\User;
use App\Models\Users;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->minLength(4)
                ->maxLength(100),
                TextInput::make('email')
                ->label('Correo Electrónico')
                ->required()
                ->email(),
                Select::make('roles')
                ->relationship('roles', 'name')   // Relación Spatie
                ->label('Rol')
                ->required()
                ->preload()
                ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable(),
                TextColumn::make('email')
                ->label('Email')
                ->searchable(),
                TextColumn::make('roles.name')
                ->label('Rol')
                ->searchable(),
                TextColumn::make('permissions.name')
                ->label('Permisos')
                ->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->iconButton(),
                Tables\Actions\DeleteAction::make()
                ->visible(fn () => Filament::auth()->user()->hasRole('Superadmin'))
                ->iconButton()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationGroup(): ?string
    {
    return 'Settings';
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            /*'create' => Pages\CreateUsers::route('/create'),
            'edit' => Pages\EditUsers::route('/{record}/edit'),*/
        ];
    }
}
