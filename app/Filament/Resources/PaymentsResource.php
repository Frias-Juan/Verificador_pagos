<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentsResource\Pages;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PaymentsResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Pagos';
    protected static ?string $modelLabel = 'Pago';
    protected static ?string $pluralModelLabel = 'Pagos';

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        return $form
            ->schema([
                // Tenant ID (oculto)
                Forms\Components\Hidden::make('tenant_id')
                    ->default($tenantId),
                
                // Payment Gateway
                Forms\Components\Select::make('payment_gateway_id')
                    ->options(function () use ($tenantId) {
                        return PaymentGateway::where('tenant_id', $tenantId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->label('Pasarela'),
                
                // Información básica
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label('Monto'),
                
                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->label('Fecha de Pago')
                    ->default(now()),
                
                Forms\Components\TextInput::make('remitter')
                    ->required()
                    ->label('Remitente'),
                
                Forms\Components\TextInput::make('reference')
                    ->numeric()
                    ->required()
                    ->label('Referencia'),
                
                Forms\Components\TextInput::make('bank')
                    ->required()
                    ->label('Banco'),
                
                Forms\Components\Toggle::make('verified')
                    ->label('Verificado')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('payment_gateway.name')
                    ->label('Pasarela')
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto Bs')
                    ->sortable(),
                
               Tables\Columns\TextColumn::make('remitter')
    ->label('Emisor')
    ->getStateUsing(function ($record) {

        $name = $record->remitter;
        $phone = $record->phone_number;

        if ($name && $phone) {
            return "{$name} ({$phone})";
        }

        if ($name) {
            return $name;
        }

        if ($phone) {
            return $phone;
        }

        return 'Desconocido';
    })
    ->searchable()
    ->sortable(),

                
                Tables\Columns\TextColumn::make('bank')
                    ->label('Banco'),
                
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->label('Fecha')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('verified')
                    ->boolean()
                    ->label('Verificado'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('verified')
                    ->label('Verificado'),
            ])
            ->actions([
                Action::make('verified')
                ->label('Verificar')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Payment $record) => !$record->verified)
                ->action(function (Payment $record) {
                    $record->update([
                        'verified' => true,
                        'verified_on' => now(),
                        'status' => 'verified',
                    ]);
                }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if (!$user->hasRole('Superadmin')) {
                    $query->where('tenant_id', $user->tenant_id);
                }
                return $query;
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayments::route('/create'),
            'edit' => Pages\EditPayments::route('/{record}/edit'),
        ];
    }
}