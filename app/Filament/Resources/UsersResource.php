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
                Forms\Components\Section::make('Configuración de Pasarela')
                    // Solo visible si el rol es Admin
                    ->visible(fn(Get $get) => $get('selectedRoleName') === 'Admin')
                    ->schema([
                        // --- PAGOS MÓVIL (PAGOMOVIL) ---
                Forms\Components\Fieldset::make('Pago Móvil')
                    ->schema([
                Forms\Components\Select::make('name')
                    ->options([
                        'Banco de Venezuela' => 'Banco Vzla (BDV)',
                    ])    
                    ->label('Banco')
                    ->required(),
            ])
            ->columns(2),
    ])
    ->columns(1),

                Forms\Components\Section::make('Asignar a negocio existente')
                    // Solo visible si el rol es Employee
                    ->visible(fn(Get $get) => $get('selectedRoleName') === 'Employee') 
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Negocio')
                            ->relationship('tenant', 'business_name')
                            ->required(fn ($context, $get) => 
                                // Requerido solo si el creador es Superadmin
                                $context === 'create' && auth()->user()->hasRole('Superadmin')
                            )
                            ->hidden(function ($context) use ($employeeRole) {
                                $creator = auth()->user();
                                // Ocultar si el creador es un Admin y está creando un Employee
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
        // ... (Tu función table es correcta)
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
            Tables\Columns\TextColumn::make('lastname')->label('Apellido')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('roles.name')->label('Rol')->badge(),
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
        // ... (Tu getEloquentQuery es correcta para scoping)
        $user = auth()->user();

        if ($user->hasRole('Superadmin')) {
            return parent::getEloquentQuery();
        }

        if ($user->hasRole('Admin')) {
            return parent::getEloquentQuery()
                ->where('tenant_id', $user->tenant_id)
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Superadmin'));
        }

        if ($user->hasRole('Employee')) {
            return parent::getEloquentQuery()
                ->where('tenant_id', $user->tenant_id)
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Superadmin'));
        }

        return parent::getEloquentQuery()->whereRaw('1=0');
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

    public static function getNavigationGroup(): ?string
    {
    return 'Settings';
    }
}