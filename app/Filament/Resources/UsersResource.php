<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsersResource\Pages;
use App\Models\User;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use App\Models\Bank;
use Illuminate\Support\Facades\Auth;

class UsersResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $pluralModelLabel = 'Users';

    public static function form(Form $form): Form
    {
        // Obtener el rol de Admin (necesario para el campo tenant_id)
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
        $employeeRole = \Spatie\Permission\Models\Role::where('name', 'Employee')->first();

        return $form
            ->schema([
                Forms\Components\Section::make('Roles y Permisos')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Rol')
                            ->relationship('roles', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Limpiar tenant_id y actualizar el rol seleccionado
                                $set('tenant_id', null);
                                $roleName = $state ? \Spatie\Permission\Models\Role::find($state)?->name : null;
                                $set('selectedRoleName', $roleName);
                            })
                            ->preload(),
                        
                        // Campo Hidden para manejar la visibilidad condicional
                        Forms\Components\Hidden::make('selectedRoleName')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateHydrated(function ($set, $state, $record) {
                                if ($record && $record->roles->count() > 0) {
                                    $set('selectedRoleName', $record->roles->first()->name);
                                }
                            }),
                    ]),

                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Nombre')->required(),
                        Forms\Components\TextInput::make('lastname')->label('Apellido')->required(),
                        Forms\Components\TextInput::make('email')->label('Correo')->email()->required()->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('cedula')->label('Cédula/RIF')->required(),
                        Forms\Components\TextInput::make('phone')->label('Teléfono')->mask('9999-9999999')->required(),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->label('Contraseña')
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state)),
                    ])
                    ->columns(2),

                // ⚠️ CAMPOS SOLO PARA CAPTURA (Se guardan en CreateUsers.php)
                Forms\Components\Section::make('Datos del negocio')
                    ->visible(fn(Get $get) => $get('selectedRoleName') === 'Admin')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->label('Nombre del negocio')
                            ->required(),
                            
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección del negocio'), 
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Configuración de Pasarelas de Pago Inicial')
                    ->visible(fn(Get $get) => $get('selectedRoleName') === 'Admin')
                    ->schema([
                        Forms\Components\Repeater::make('initial_gateways') // No es una relación, solo captura data
                            ->label('Añadir Pasarela de Pago')
                            ->schema([
                                
                                // 1. SELECT PARA EL TIPO DE PASARELA
                                Forms\Components\Select::make('gateway_type') 
                                    ->label('Tipo de Pasarela')
                                    ->options([
                                        'PAGOMOVIL' => 'Pago Móvil',
                                        'ZELLE' => 'Zelle',
                                    ])
                                    ->live() 
                                    ->required(),

                                // 2. CAMPO NOMBRE: Condicional basado en el tipo

                                // a) Si es Pago Móvil, mostramos la lista de Bancos
                                Forms\Components\Select::make('gateway_name') 
                                    ->label('Nombre del Banco (Pago Móvil)')
                                    ->options(Bank::pluck('name', 'name'))
                                    ->required()
                                    ->hidden(fn(Get $get) => $get('gateway_type') !== 'PAGOMOVIL')
                                    ->placeholder('Seleccione el banco para Pago Móvil'),
                                
                                // b) Si es Zelle, mostramos un campo de texto libre para el nombre de la cuenta
                                Forms\Components\TextInput::make('zelle_name') 
                                    ->label('Nombre de la Cuenta (Zelle)')
                                    ->placeholder('Ej: Cuenta de John Doe')
                                    ->required()
                                    ->hidden(fn(Get $get) => $get('gateway_type') !== 'ZELLE'),

                            ])
                            ->minItems(1)
                            ->maxItems(5) 
                            ->addActionLabel('Añadir otra pasarela')
                            ->columns(3),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Asignar a negocio existente')
                    // Solo visible si el rol es Employee
                    ->visible(fn(Get $get) => $get('selectedRoleName') === 'Employee') 
                    ->schema([
                        // Si el usuario Employee pertenece a un Tenant, usa tenant_id
                        Forms\Components\Select::make('tenant_id')
                            ->label('Negocio')
                            ->relationship('tenant', 'business_name') // Asumo relación belongsTo en el modelo User
                            ->required(fn ($context, $get) => 
                                $context === 'create' && auth()->user()->hasRole('Superadmin')
                            )
                            ->hidden(function ($context) use ($employeeRole) {
                                $creator = auth()->user();
                                if ($creator && $creator->hasRole('Admin')) {
                                    return true; 
                                }
                                return false;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
            Tables\Columns\TextColumn::make('lastname')->label('Apellido')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('roles.name')->label('Rol')->badge(),
            // Asumo que el modelo User tiene una relación belongsTo llamada 'tenant'
            Tables\Columns\TextColumn::make('tenant.business_name')->label('Negocio'), 
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()->hasRole('Superadmin')),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
    
    $query = parent::getEloquentQuery();
    
    if (!$user->hasRole('Superadmin') && $user->tenant_id) {
        $query->where('tenant_id', $user->tenant_id);
    }
    
    return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUsers::route('/create'),
            'edit' => Pages\EditUsers::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->hasRole('Superadmin');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_user::resource');
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
    
    if ($user->hasRole('Superadmin')) {
        return true;
    }
    
    return $user->can('update_user::resource') 
           && $record->tenant_id === $user->tenant_id
           && $record->id !== $user->id;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

    if ($user->hasRole('Superadmin')) {
        return true;
    }
    
    return $user->can('delete_user::resource')
           && $record->tenant_id === $user->tenant_id
           && $record->id !== $user->id;
    }

    public static function getNavigationGroup(): ?string
    {
    return 'Settings';
    }
}