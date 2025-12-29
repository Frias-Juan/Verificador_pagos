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
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsersResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $slug = 'usuarios';

    public static function form(Form $form): Form
    {
        // Obtener el rol de Admin (necesario para el campo tenant_id)
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
        $employeeRole = \Spatie\Permission\Models\Role::where('name', 'Employee')->first();

        return $form
            ->schema([
                Forms\Components\Section::make('Rol')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('tenant_id', null);
                                $roleName = $state ? \Spatie\Permission\Models\Role::find($state)?->name : null;
                                $set('selectedRoleName', $roleName);
                            })
                            ->preload(),
                        
                        Forms\Components\Hidden::make('selectedRoleName')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateHydrated(function ($set, $state, $record) {
                                if ($record && $record->roles->count() > 0) {
                                    $set('selectedRoleName', $record->roles->first()->name);
                                }
                            }),
                        ]),
                        Forms\Components\Section::make('Permisos')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('Permisos')
                                            ->relationship(
                                                'permissions', 
                                                'name',
                                                fn (Builder $query) => $query
                                                    ->where('name', 'like', 'view_any_%') 
                                                    ->where('name', 'not like', '%role%')
                                                    ->where('name', 'not like', '%permission%')
                                            )
                                            ->columns(2)
                                            ->bulkToggleable()
                                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                str($record->name)
                                                    ->replace('view_any_', '')
                                                    ->replace('::resource', '')
                                                    ->replace('_', ' ')
                                                    ->prepend('Gestionar' . ' ')
                                                    ->title()
                                            )
                                            ->searchable()
                                            ->bulkToggleable()
                    
                            ]),
                Forms\Components\Section::make('Información Personal')
    ->schema([
        Forms\Components\TextInput::make('name')
            ->label('Nombre')
            ->required(),
        Forms\Components\TextInput::make('lastname')
            ->label('Apellido')
            ->required(),
        Forms\Components\TextInput::make('email')
            ->label('Correo')
            ->email()
            ->required()
            ->unique(ignoreRecord: true),
      
        Forms\Components\TextInput::make('cedula')
            ->label('Cédula')
            ->maxLength(8)
            ->required()
            ->unique(ignoreRecord: true),
      
        Forms\Components\TextInput::make('phone')
            ->label('Teléfono')
            ->mask('9999-9999999')
            ->required()
            ->unique(ignoreRecord: true),
      
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
                            ->nullable() // Permite que sea nulo al editar
            ->required(fn ($context) => $context === 'create'),
                            
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección del negocio'), 
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Configuración de Pasarelas de Pago Inicial')
                    ->visible(fn(Get $get) => $get('selectedRoleName') === 'Admin')
                    ->schema([
                        Forms\Components\Repeater::make('initial_gateways') 
                            ->label('Añadir Pasarela de Pago')
                            ->schema([
                                Forms\Components\Select::make('gateway_type') 
                                    ->label('Tipo de Pasarela')
                                    ->options([
                                        'PAGOMOVIL' => 'Pago Móvil'
                                    ])
                                    ->live() 
                                    ->required(),

                                Forms\Components\Select::make('gateway_name') 
                                    ->label('Nombre del Banco (Pago Móvil)')
                                    ->options(Bank::pluck('name', 'name'))
                                    ->required(fn(Get $get) => $get('gateway_type') === 'PAGOMOVIL')
                                    ->hidden(fn(Get $get) => $get('gateway_type') !== 'PAGOMOVIL')
                                    ->placeholder('Seleccione el banco para Pago Móvil'),
                                
                                Forms\Components\TextInput::make('zelle_name') 
                                    ->label('Nombre de la Cuenta (Zelle)')
                                    ->placeholder('Ej: Cuenta de John Doe')
                                    ->nullable() // Permite que sea nulo al editar
                                    ->required(fn ($context) => $context === 'create')
                                    ->hidden(fn(Get $get) => $get('gateway_type') !== 'ZELLE'),

                            ])
                            ->minItems(fn ($context) => $context === 'create' ? 1 : 0) // 1 al crear, 0 al editar
                            ->required(fn ($context) => $context === 'create') // Requerido solo al crear
                            ->maxItems(5) 
                            ->addActionLabel('Añadir otra pasarela')
                            ->columns(3),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Asignar a negocio existente')
                    ->visible(fn(Get $get) => $get('selectedRoleName') === 'Employee') 
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Negocio')
                            ->relationship('tenant', 'business_name') 
                            ->required(fn ($context, $get) => 
                                $context === 'create' && auth()->user()->hasRole('Superadmin')
                            )
                            ->nullable() // Permite que sea nulo al editar
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
        return $table
        ->recordUrl(null)
        ->columns([
            Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
            Tables\Columns\TextColumn::make('lastname')->label('Apellido')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('roles.name')->label('Rol')->badge(),
            // Asumo que el modelo User tiene una relación belongsTo llamada 'tenant'
            Tables\Columns\TextColumn::make('tenant.business_name')->label('Negocio'), 
        ])
        ->actions([
           Tables\Actions\Action::make('aprobar')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                // ESTO ES LO QUE BUSCAS: Solo visible si el estatus es 'pending'
                ->visible(fn ($record) => $record->status === 'pending')
                ->action(function ($record) {
                    $record->update(['status' => 'approved']);

                    // Limpiamos la notificación de la base de datos
                    DB::table('notifications')
                        ->where('data', 'like', '%"user_id":' . $record->id . '%')
                        ->delete();

                    Notification::make()
                        ->title('Usuario aprobado')
                        ->success()
                        ->send();
                }),
            Tables\Actions\Action::make('rechazar')
                ->label('Rechazar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => $record->status === 'pending')
                ->action(function ($record) {
                    DB::table('notifications')
                        ->where('data', 'like', '%"user_id":' . $record->id . '%')
                        ->delete();

                    $record->delete();

                    Notification::make()
                        ->title('Usuario eliminado')
                        ->danger()
                        ->send();
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),        
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                ->visible(fn() => auth()->user()->hasRole('Superadmin')),
        ]);
    }

    public static function canViewAny(): bool
    {
        // Solo el Superadmin puede ver este Resource en el menú
        return Auth::user()->hasRole('Superadmin');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->hasRole('Superadmin');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->hasRole('Superadmin');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->hasRole('Superadmin');
    }

    public static function getEloquentQuery(): Builder
    {
        // El Superadmin siempre ve TODOS los usuarios del sistema
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUsers::route('/create'),
            'edit' => Pages\EditUsers::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
    return 'Settings';
    }
}