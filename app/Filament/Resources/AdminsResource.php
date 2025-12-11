<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminsResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AdminsResource extends Resource
{
    protected static ?string $model = Tenant::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationLabel = 'Negocios';
    
    protected static ?string $modelLabel = 'Negocio';
    
    protected static ?string $pluralModelLabel = 'Negocios';
    
    protected static ?string $title = 'Gestión de negocios';
    
    protected static ?string $slug = 'admins-negocios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Negocio')
                    ->description('Datos principales del negocio')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->label('Nombre del Negocio')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set) {
                                if (empty($state)) return;
                                $set('slug', Str::slug($state));
                            }),
                            
                        Forms\Components\Select::make('owner_id')
                            ->label('Propietario')
                            ->relationship(
                                name: 'owner',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereHas('roles', function ($q) {
                                    $q->where('name', 'Admin');
                                })
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                            /*->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->unique(),
                                Forms\Components\TextInput::make('password')
                                    ->label('Contraseña')
                                    ->password()
                                    ->required()
                                    ->minLength(8),
                            ])
                            ->createOptionUsing(function ($data) {
                                $user = \App\Models\User::create([
                                    'name' => $data['name'],
                                    'email' => $data['email'],
                                    'password' => bcrypt($data['password']),
                                ]);
                                $user->assignRole('Admin');
                                return $user->id;
                            }),*/
                    TextInput::make('domains')
                    ->label('Dominio')
                    ->placeholder('(Opcional)'),
                    
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Negocio')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->slug)
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propietario')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->owner->email ?? '')
                    ->url(fn ($record) => $record->owner ? 
                        \App\Filament\Resources\UsersResource::getUrl('edit', ['record' => $record->owner_id]) : 
                        null
                    )
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('domains')
                    ->label('Dominios')
                    ->badge()
                    ->color('success')
                    ->separator(', ')
                    ->limitList(2)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payment_gateway')
                    ->label('Pasarelas de pago'),    
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
                            
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),
                        
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),
                        
                    Tables\Actions\Action::make('impersonate')
                        ->label('Acceder como Admin')
                        ->icon('heroicon-o-arrow-right-on-rectangle')
                        ->color('success')
                        ->url(fn (Tenant $record) => url('/admin/switch-tenant/' . $record->id))
                        ->openUrlInNewTab()
                        ->visible(fn (): bool => auth()->user()->hasRole('Superadmin')),
                        
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Negocio')
                        ->modalDescription('¿Estás seguro de eliminar este negocio? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->successNotificationTitle('Negocio eliminado'),
                ])->icon('heroicon-o-ellipsis-vertical')
                  ->button()
                  ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()->hasRole('Superadmin')),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear Nuevo Negocio')
                    ->icon('heroicon-o-plus'),
            ])
            ->emptyStateDescription('No hay negocios registrados. Crea el primero.')
            ->emptyStateIcon('heroicon-o-building-office')
            ->deferLoading()
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['owner', 'domains']);
        
        // Superadmin ve todos los negocios
        if (auth()->user()->hasRole('Superadmin')) {
            return $query;
        }
        
        // Tenant Admin solo ve sus propios negocios
        if (auth()->user()->hasRole('Admin')) {
            return $query->where('owner_id', auth()->id());
        }
        
        // Employee no ve negocios
        return $query->whereRaw('1 = 0');
    }

    public static function getRelations(): array
    {
        return [
            // Descomenta si quieres agregar relation managers
            // \App\Filament\Resources\AdminsResource\RelationManagers\PaymentGatewaysRelationManager::class,
            // \App\Filament\Resources\AdminsResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    
   
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmins::route('/create'),
            'edit' => Pages\EditAdmins::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['Superadmin', 'Admin']);
    }
    
    public static function canEdit($record): bool
    {
        if (auth()->user()->hasRole('Superadmin')) {
            return true;
        }
        
        if (auth()->user()->hasRole('Admin')) {
            return $record->owner_id === auth()->id();
        }
        
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return auth()->user()->hasRole('Superadmin');
    }
}