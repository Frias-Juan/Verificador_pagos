<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminsResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminsResource extends Resource
{
    protected static ?string $model = Tenant::class;
    
    // Etiquetas en español
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Negocios';
    protected static ?string $modelLabel = 'Negocio';
    protected static ?string $pluralModelLabel = 'Negocios';
    protected static ?string $title = 'Gestión de Negocios';
    protected static ?string $slug = 'negocios';

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
                            ->label('Propietario (Admin)')
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
                            
                        Forms\Components\TextInput::make('domains')
                            ->label('Dominio')
                            ->placeholder('mi-negocio.com (Opcional, separado por comas)'),
                            
                        // CAMPO DE PASARELAS (Muchos a Muchos)
                        Forms\Components\Select::make('paymentGateways')
                            ->label('Pasarelas de Pago')
                            ->relationship(
                                name: 'paymentGateways',
                                titleAttribute: 'name'
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Selecciona las pasarelas de pago existentes que deseas asociar a este negocio.'),

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
                    ->description(fn (?Tenant $record) => $record?->owner->email ?? '')
                    ->url(fn (?Tenant $record) => $record?->owner ? 
                        \App\Filament\Resources\UsersResource::getUrl('edit', ['record' => $record->owner_id]) : 
                        null
                    )
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('domains')
                    ->label('Dominios')
                    ->formatStateUsing(function ($state, ?Tenant $record) {
                        if ($record === null || empty($state)) {
                            return null;
                        }
                        if (is_array($state)) {
                            return implode(', ', array_filter($state));
                        }
                        return $state;
                    })
                    ->badge()
                    ->color('success')
                    ->separator(', ')
                    ->limitList(2)
                    ->toggleable()
                    ->visible(fn (?Tenant $record): bool => 
                        $record !== null && !empty($record->domains) && 
                        (is_string($record->domains) || is_array($record->domains))
                    ),

                // Columna de Pasarelas de Pago
                Tables\Columns\TextColumn::make('id') 
                    ->label('Pasarelas de Pago')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state, Tenant $record) {
                        $gateways = $record->paymentGateways;
                        
                        if ($gateways->isEmpty()) {
                            return null;
                        }

                        return $gateways
                            ->unique('id') 
                            ->map(function ($gateway) {
                                $type = $gateway->type === 'PAGOMOVIL' ? 'Pago Móvil' : ($gateway->type === 'ZELLE' ? 'Zelle' : $gateway->type);
                                return "{$type}: {$gateway->name}";
                            })->implode(', ');
                    })
                    ->limitList(2)
                    ->listWithLineBreaks(false), 

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtros
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('impersonate')
                        ->label('Acceder como Admin')
                        ->icon('heroicon-o-arrow-right-on-rectangle')
                        ->color('success')
                        ->url(fn (Tenant $record) => url('/admin/switch-tenant/' . $record->id))
                        ->openUrlInNewTab()
                        ->visible(fn (): bool => auth()->user()->hasRole('Superadmin')),
                        
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (): bool => auth()->user()->hasRole('Superadmin')), 
                ])->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()->hasRole('Superadmin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
    
    $query = parent::getEloquentQuery();
    
    if (!$user->hasRole('Superadmin') && $user->tenant_id) {
        $query->where('id', $user->tenant_id);
    }
    
    return $query;
    }
    
    // Métodos de navegación y permisos
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
        return Auth::user()->hasRole('Superadmin');
    }
    
    public static function canEdit($record): bool
    {
        return Auth::user()->hasRole('Superadmin')|| $record->id === Auth::user()->tenant_id;
    }
    
    public static function canDelete($record): bool
    {
        return Auth::user()->hasRole('Superadmin')|| $record->id === Auth::user()->tenant_id;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_tenant::resource');
    }


}