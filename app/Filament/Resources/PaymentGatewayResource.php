<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGateway;
use App\Models\Bank; // Importamos el modelo Bank para el Select
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get; // Importar para lógica condicional

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    // Etiquetas y nombres en español
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Pasarelas de Pago';
    protected static ?string $modelLabel = 'Pasarela de Pago';
    protected static ?string $pluralModelLabel = 'Pasarelas de Pago';
    protected static ?string $slug = 'pasarelas-pago';
    
    // Asumiendo que solo los Admins y Superadmins deben ver este menú
    public static function canViewAny(): bool
    {
        return Auth::user()->hasAnyRole(['Superadmin', 'Admin']);
    }

    // --- FORMULARIO (CREAR/EDITAR) ---
    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('Superadmin');

        return $form
            ->schema([
                Forms\Components\Fieldset::make('Detalles de la Pasarela')
                    ->columns(3)
                    ->schema([
                        // 1. TIPO DE PASARELA (PAGOMOVIL/ZELLE)
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Pasarela')
                            ->options([
                                'PAGOMOVIL' => 'Pago Móvil',
                                'ZELLE' => 'Zelle',
                            ])
                            ->live() // Necesario para la lógica condicional del nombre
                            ->required()
                            ->native(false)
                            ->searchable(),

                        // 2. CAMPO NOMBRE (Condicional basado en el tipo)
                        Forms\Components\Group::make([
                            // SELECT: Si es Pago Móvil, mostramos la lista de Bancos
                            Forms\Components\Select::make('name') 
                                ->label('Banco/Nombre de la Cuenta')
                                ->options(Bank::pluck('name', 'name'))
                                ->required()
                                ->hidden(fn(Get $get) => $get('type') !== 'PAGOMOVIL')
                                ->placeholder('Seleccione el Banco'),
                            
                            // TEXT INPUT: Si es Zelle, pedimos el nombre de la cuenta
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre de la Cuenta (Zelle)')
                                ->required()
                                ->hidden(fn(Get $get) => $get('type') !== 'ZELLE')
                                ->placeholder('Ej: Cuenta de John Doe'),

                        ])->columnSpan(1),

                        // 3. ESTADO
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                    ]),

                // 4. NEGOCIO (Solo visible y editable por Superadmin)
                Forms\Components\Fieldset::make('Asignación de Negocio')
                    ->columns(1)
                    ->schema([
                        $isSuperadmin 
                            ? Forms\Components\Select::make('tenant_id')
                                ->label('Negocio Asignado')
                                ->relationship(
                                    name: 'tenant', 
                                    titleAttribute: 'business_name' 
                                )
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false)
                            : Forms\Components\Hidden::make('tenant_id')
                                ->default($user->tenant_id),
                    ])
                    ->visible($isSuperadmin), // Ocultar sección si no es Superadmin
            ]);
    }

    // --- TABLA (LISTADO) ---
    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('Superadmin');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre / Banco')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de Pasarela')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'PAGOMOVIL' => 'Pago Móvil',
                        'ZELLE' => 'Zelle',
                        default => $state,
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->sortable()
                    ->boolean(), 
                    
                Tables\Columns\TextColumn::make('tenants.business_name')
                    ->label('Negocio')
                    ->searchable()
                    ->sortable()
                    ->visible($isSuperadmin), // Solo visible para Superadmin
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por Negocio (Solo para Superadmin)
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'business_name')
                    ->label('Filtrar por Negocio')
                    ->visible($isSuperadmin),
                    
                // Filtro por Tipo de Pasarela
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filtrar por Tipo')
                    ->options([
                        'PAGOMOVIL' => 'Pago Móvil',
                        'ZELLE' => 'Zelle',
                    ]),
                    
                // Filtro de Estado
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                ->label('Acciones en Lote'),
            ]);
    }

    // --- SCOPING (FILTRADO DE DATOS) ---
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        $query = parent::getEloquentQuery();
        
        if (!$user->hasRole('Superadmin')) {
            // Admin normal solo ve sus propios gateways (scoping por tenant_id)
            $query->where('tenant_id', $user->tenant_id);
        }
        
        return $query;
    }

    // --- DATOS ANTES DE CREAR (Asignación automática de tenant_id si no es Superadmin) ---
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        
        // Si el usuario no es Superadmin, asignamos automáticamente su tenant_id
        if (!$user->hasRole('Superadmin') && empty($data['tenant_id'])) {
            $data['tenant_id'] = $user->tenant_id;
        }
        
        // Filament necesita solo uno de los campos 'name' para el guardado. 
        // Eliminamos el campo nulo para evitar conflictos con el Fieldset condicional en la DB.
        
        // Si el tipo es PAGOMOVIL, el campo 'name' tiene el valor, el otro 'name' (Zelle) será null.
        // Si el tipo es ZELLE, el campo 'name' tiene el valor (TextInput).
        // NOTA: Si usaste dos campos TextInput distintos, asegúrate de que el valor correcto se asigne a 'name'.
        // Aquí asumimos que el valor guardado en el form tiene la clave 'name' con el dato correcto.

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentGateways::route('/'),
            'create' => Pages\CreatePaymentGateway::route('/create'),
            'edit' => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }
}