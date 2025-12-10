<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsersResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $modelLabel = 'User';
    protected static ?string $pluralModelLabel = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                 Forms\Components\Section::make('Roles y Permisos')
                            ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable()
                            ->required(),
                            
                        
                    ]),

                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->minLength(2)
                            ->maxLength(100),
                            
                        Forms\Components\TextInput::make('lastname')
                            ->label('Apellido')
                            ->required()
                            ->minLength(2)
                            ->maxLength(100),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->confirmed(),
                            
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(false),
                            TextInput::make('cedula')
                            ->label('Cédula/RIF')
                            ->required(),
                             TextInput::make('phone')
                            ->label('Teléfono')
                            ->required()
                            ->mask('9999-9999999'),
                            ])->columns(2),
                    
                       
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('lastname')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->separator(', ')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Negocio')
                    ->relationship('tenant', 'business_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()->hasRole('Superadmin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()->hasRole('Superadmin')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Superadmin ve todos los usuarios
        if (auth()->user()->hasRole('Superadmin')) {
            return $query;
        }
        
        // Tenant Admin ve usuarios de SU tenant
        if (auth()->user()->hasRole('Tenant Admin') && auth()->user()->tenant_id) {
            return $query->where('tenant_id', auth()->user()->tenant_id);
        }
        
        // Employee ve solo usuarios de SU tenant (excepto Superadmin)
        if (auth()->user()->hasRole('Employee') && auth()->user()->tenant_id) {
            return $query->where('tenant_id', auth()->user()->tenant_id)
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'Superadmin');
                });
        }
        
        // Por defecto, no ver nada
        return $query->whereRaw('1 = 0');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getNavigationGroup(): ?string
    {
    return 'Settings';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUsers::route('/create'),
            'edit' => Pages\EditUsers::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['Superadmin', 'Admin']);
    }
}