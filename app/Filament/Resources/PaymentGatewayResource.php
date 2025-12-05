<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayResource\Pages;
use App\Filament\Resources\PaymentGatewayResource\RelationManagers;
use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('Superadmin');

        $esquema = [

             $isSuperadmin 
                ? Forms\Components\Select::make('tenant_id')
                        ->label('Negocio')
                        ->relationship(
                            name: 'tenant', 
                            titleAttribute: 'business_name' // o 'id' o lo que uses
                        )
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                : Forms\Components\Hidden::make('tenant_id')
                        ->default($user->tenant_id),
                Forms\Components\TextInput::make('name')
                    ->label('Banco')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('api_key')
                    ->required()
                    ->label('API_KEY'),
                /*Forms\Components\Select::make('user_id')
                // 1. Usar la relación SINGULAR: 'user'
                // 2. Usar 'name' como columna principal (o la que más sentido tenga)
                ->relationship('user', 'name')
                ->label('Usuario (Cédula y Teléfono)')
                ->required()
                // 3. Personalizar la etiqueta de la opción para mostrar todos los datos
                ->getOptionLabelFromRecordUsing(fn (Model $record) => 
                    "{$record->name} - Cédula: {$record->cedula} - Teléfono: {$record->phone}"
                )*/

        ];




        return $form
            ->schema($esquema);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('Superadmin');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nombre'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
            ]);

             if ($isSuperadmin) {
            array_unshift($columns, 
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Negocio')
                    ->searchable()
                    ->sortable()
            );
        }
        
        return $table
            ->columns($columns)

            ->filters([
                Tables\Filters\SelectFilter::make('tenant')
                    ->relationship('tenant', 'name')
                    ->label('Negocio')
                    ->visible($isSuperadmin),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        $query = parent::getEloquentQuery();
        
        if (!$user->hasRole('Superadmin')) {
            // Admin normal solo ve sus propios gateways
            $query->where('tenant_id', $user->tenant_id);
        }
        
        return $query;
    }

     protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        
        if (!$user->hasRole('Superadmin') && empty($data['tenant_id'])) {
            $data['tenant_id'] = $user->tenant_id;
        }
        
        return $data;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
