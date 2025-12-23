<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminsResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\Action; 
class AdminsResource extends Resource
{
    protected static ?string $model = Tenant::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Negocio'; 
    protected static ?string $modelLabel = 'Negocio';
    protected static ?string $pluralModelLabel = 'Negocios';
    protected static ?string $slug = 'negocios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Negocio')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->label('Nombre del Negocio')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                            
                        Forms\Components\Select::make('owner_id')
                            ->label('Propietario')
                            ->relationship('owner', 'name', fn ($query) => 
                                $query->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
                            )
                            ->disabled(!auth()->user()->hasRole('Superadmin')) // Solo el Superadmin cambia dueños
                            ->required(),

                        Forms\Components\Select::make('paymentGateways')
                            ->label('Pasarelas de Pago Habilitadas')
                            ->relationship('paymentGateways', 'name')
                            ->multiple()
                            ->preload()
                            ->helperText('Selecciona las pasarelas que este negocio puede usar.'),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Negocio')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->slug),
                    
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propietario')
                    ->getStateUsing(fn ($record) => "{$record->owner->name} {$record->owner->lastname}")
                    ->visible(fn() => auth()->user()->hasRole('Superadmin')), // Solo el SA ve el dueño en la tabla
                
                Tables\Columns\TextColumn::make('staff')
    ->label('Empleados')
    ->view('filament.tables.columns.accordion-staff')
    ->getStateUsing(function ($record) {
        return $record->users()
            ->role('Employee')
            ->get()
            ->map(fn($user) => "{$user->name} {$user->lastname}")
            ->toArray();
    }),
                 
                Tables\Columns\TextColumn::make('paymentGateways.name')
                    ->label('Pasarelas')
                    ->badge()
                    ->color('info'),
                 Tables\Columns\IconColumn::make('paymentGateways.is_active')
                    ->label('Estado')
                    ->boolean(),    
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
               
                Tables\Actions\Action::make('impersonate')
                    ->label('Entrar como')
                    ->icon('heroicon-o-finger-print')
                    ->color('warning')
                    ->visible(fn ($record) => 
                        auth()->user()->hasRole('Superadmin') && 
                        $record->owner !== null && 
                        method_exists($record->owner, 'canBeImpersonated') && 
                        $record->owner->canBeImpersonated()
                    )
                    ->action(function ($record) {
                        // 1. Guardamos al usuario actual (Superadmin)
                        $superadmin = auth()->user();

                        // 2. Iniciamos la suplantación del dueño
                        $superadmin->impersonate($record->owner);

                        // 3. Limpiamos los hashes de sesión de forma manual y segura
                        // Usamos nombres de clave estándar de Laravel/Filament
                        session()->forget([
                            'password_hash_web',
                            'password_hash_filament',
                            'password_hash_sanctum',
                        ]);

                        // 4. Redirigir al dashboard
                        // Usamos la ruta hardcoded si config() falla para asegurar el tiro
                        return redirect()->to('/admin');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('Superadmin')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();
        
        // El Admin solo ve SU negocio. El Superadmin ve todos.
        if (!$user->hasRole('Superadmin')) {
            $query->where('id', $user->tenant_id);
        }
        
        return $query;
    }

    // --- AUTORIZACIÓN ---

    public static function canViewAny(): bool
    {
        // Usamos el permiso en PLURAL como definimos en el seeder
        return Auth::user()->can('view_any_admins::resource');
    }

    public static function canCreate(): bool
    {
        // Solo el Superadmin crea negocios desde cero
        return Auth::user()->hasRole('Superadmin');
    }

    public static function canEdit(Model $record): bool
    {
        // El Superadmin edita todos, el Admin solo el suyo
        return Auth::user()->hasRole('Superadmin') || $record->id === Auth::user()->tenant_id;
    }

    public static function canDelete(Model $record): bool
    {
        // Solo el Superadmin puede borrar negocios
        return Auth::user()->hasRole('Superadmin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmins::route('/create'),
            'edit' => Pages\EditAdmins::route('/{record}/edit'),
        ];
    }
}