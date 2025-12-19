<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\Pages\CreateEmployees;
use App\Filament\Resources\EmployeesResource\Pages\EditEmployees;
use App\Filament\Resources\EmployeesResource\Pages\ListEmployees;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class EmployeesResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Mis Empleados';
    protected static ?string $modelLabel = 'Empleado';
    protected static ?string $pluralModelLabel = 'Empleados';
    protected static ?string $slug = 'empleados';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Empleado')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                       Forms\Components\Select::make('tenant_id')
                            ->label('Negocio / Empresa')
                            ->relationship('tenants', 'business_name') // Relación con el modelo Tenant
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()->tenant_id)
                            ->required()
                            // Si el usuario es Admin, ocultamos el selector (porque ya sabemos su tenant)
                            // Si es Superadmin, lo dejamos visible para que elija el negocio
                            ->disabled(fn () => !auth()->user()->hasRole('Superadmin'))
                            ->dehydrated(true)
                            ->visible(fn() => auth()->user()->hasRole('Superadmin')),
                        Forms\Components\TextInput::make('lastname')
                            ->label('Apellido')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('cedula')
                            ->label('Cédula')
                            ->maxLength(8)
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->mask('9999-9999999')
                            ->required(),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->label('Contraseña')
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state)),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->searchable(),
            Tables\Columns\TextColumn::make('lastname')
                ->label('Apellido')
                ->searchable(),
            Tables\Columns\TextColumn::make('email')
                ->label('Correo Electrónico')
                ->searchable(),
            Tables\Columns\TextColumn::make('cedula')
                ->label('Cédula'),
            // Mostramos el nombre del negocio (opcional para el Admin)
            Tables\Columns\TextColumn::make('tenant.business_name')
                ->label('Negocio')
                ->badge()
                ->color('info'),
        ])
            ->filters([
                // Filtros adicionales si los necesitas
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * EL CORAZÓN DEL FILTRADO:
     * Aquí aseguramos que el Admin SOLO vea a los empleados de su propio negocio.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        // Iniciamos la consulta base
        $query = parent::getEloquentQuery();

        // 1. Si no es Superadmin, filtrar por el tenant del usuario autenticado
        if (!$user->hasRole('Superadmin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        // 2. Solo mostrar usuarios que tengan el rol de 'Employee'
        // Esto evita que el Admin se vea a sí mismo en esta lista
        return $query->whereHas('roles', function ($q) {
            $q->where('name', 'Employee');
        });
    }

    // --- AUTORIZACIÓN ---

    public static function canViewAny(): bool
    {
        // El Admin debe tener permiso 'view_any_users::resource' según tu seeder
        return Auth::user()->hasRole('Admin') || Auth::user()->hasRole('Superadmin');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployees::route('/create'),
            'edit' => EditEmployees::route('/{record}/edit'),
        ];
    }
}