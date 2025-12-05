<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentsResource\Pages\CreatePayments;
use App\Filament\Resources\PaymentsResource\Pages\EditPayments;
use App\Filament\Resources\PaymentsResource\Pages\ListPayments;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentsResource extends Resource
{
    protected static ?string $model = Payment::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Pagos';
    
    protected static ?string $modelLabel = 'Pago';
    
    protected static ?string $pluralModelLabel = 'Pagos';
    
    protected static ?string $navigationGroup = 'Finanzas';

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        
        return $form
            ->schema([
                 // Tenant ID
                Forms\Components\Hidden::make('tenant_id')
                    ->default($user->tenant_id),
                    
                // Payment Gateway - SOLUCIÓN PARA EL ERROR
                Forms\Components\Select::make('payment_gateway_id')
                    ->options(
                        PaymentGateway::query()
                            ->when(!$user->hasRole('Superadmin'), function ($query) use ($user) {
                                return $query->where('tenant_id', $user->tenant_id);
                            })
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->label('Pasarela de Pago')
                    ->searchable()
                    ->preload(),
                
                // Información del pago
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label('Monto ($)')
                    ->prefix('$'),
                    
                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->label('Fecha del Pago')
                    ->default(now()),
                    
                Forms\Components\TextInput::make('remitter')
                    ->required()
                    ->label('Nombre del Remitente')
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('phone_number')
                    ->label('Teléfono del Remitente')
                    ->tel()
                    ->maxLength(20)
                    ->nullable(),
                    
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->numeric()
                    ->label('Número de Referencia')
                    ->unique(ignoreRecord: true),
                    
                Forms\Components\TextInput::make('bank')
                    ->required()
                    ->label('Banco')
                    ->maxLength(255),
                
                // Estado del pago
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'pending_verification' => 'Por Verificar',
                        'verified' => 'Verificado',
                        'rejected' => 'Rechazado',
                    ])
                    ->default('pending')
                    ->label('Estado')
                    ->required()
                    ->visible(fn () => $user->hasAnyRole(['Superadmin', 'Admin'])),
                
                // Verificación
                Forms\Components\Toggle::make('verified')
                    ->label('Verificado')
                    ->default(false)
                    ->visible(fn () => $user->hasAnyRole(['Superadmin', 'Admin'])),
                    
                Forms\Components\DatePicker::make('verified_on')
                    ->label('Fecha de Verificación')
                    ->nullable()
                    ->visible(fn () => $user->hasAnyRole(['Superadmin', 'Admin'])),
                
                // Datos de notificación (si viene de SMS)
                Forms\Components\Textarea::make('notification_data')
                    ->label('Datos de Notificación (JSON)')
                    ->nullable()
                    ->columnSpanFull()
                    ->visible(fn () => $user->hasAnyRole(['Superadmin', 'Admin'])),
                    
                Forms\Components\Select::make('notification_source')
                    ->options([
                        'sms' => 'SMS',
                        'manual' => 'Manual',
                        'email' => 'Email',
                    ])
                    ->label('Origen de notificación')
                    ->nullable()
                    ->visible(fn () => $user->hasAnyRole(['Superadmin', 'Admin'])),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        
        // Columnas base para todos
        $columns = [
            Tables\Columns\TextColumn::make('reference')
                ->searchable()
                ->sortable()
                ->label('Referencia'),
                
            Tables\Columns\TextColumn::make('paymentGateway.name')
                ->label('Pasarela')
                ->sortable(),
                
            Tables\Columns\TextColumn::make('amount')
                ->money('USD')
                ->sortable()
                ->label('Monto'),
                
            Tables\Columns\TextColumn::make('remitter')
                ->searchable()
                ->label('Remitente'),
                
            Tables\Columns\TextColumn::make('bank')
                ->label('Banco')
                ->searchable(),
                
            Tables\Columns\TextColumn::make('payment_date')
                ->date()
                ->sortable()
                ->label('Fecha'),
        ];
        
        // Solo Superadmin y Admin ven estas columnas
        if ($user->hasAnyRole(['Superadmin', 'Admin'])) {
            $columns[] = Tables\Columns\IconColumn::make('verified')
                ->boolean()
                ->label('Verificado')
                ->sortable();
                
            $columns[] = Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'pending_verification' => 'gray',
                    'verified' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'pending' => 'Pendiente',
                    'pending_verification' => 'Por Verificar',
                    'verified' => 'Verificado',
                    'rejected' => 'Rechazado',
                    default => $state,
                })
                ->label('Estado')
                ->sortable();
        }
        
        // Solo Superadmin ve la columna de Tenant
        if ($user->hasRole('Superadmin')) {
            array_unshift($columns, 
                Tables\Columns\TextColumn::make('tenant.id')
                    ->label('Tenant')
                    ->sortable()
            );
        }
        
        return $table
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'pending_verification' => 'Por Verificar',
                        'verified' => 'Verificado',
                        'rejected' => 'Rechazado',
                    ])
                    ->label('Estado')
                    ->visible(fn () => Auth::user()->hasAnyRole(['Superadmin', 'Admin'])),
                    
                Tables\Filters\SelectFilter::make('payment_gateway_id')
                    ->relationship('paymentGateway', 'name')
                    ->label('Pasarela')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => 
                                    $query->whereDate('payment_date', '>=', $date)
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => 
                                    $query->whereDate('payment_date', '<=', $date)
                            );
                    }),
                    
                Tables\Filters\TernaryFilter::make('verified')
                    ->label('Solo verificados')
                    ->nullable()
                    ->visible(fn () => Auth::user()->hasAnyRole(['Superadmin', 'Admin'])),
            ])
            ->actions([
                // Acción de verificar (solo Superadmin y Admin)
                Tables\Actions\Action::make('verify')
                    ->action(fn (Payment $record) => $record->update([
                        'verified' => true,
                        'verified_on' => now(),
                        'status' => 'verified'
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Verificar Pago')
                    ->modalDescription('¿Marcar este pago como verificado?')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $record): bool => 
                        !$record->verified && 
                        Auth::user()->hasAnyRole(['Superadmin', 'Admin'])
                    ),
                
                // Editar (solo Superadmin y Admin)
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => 
                        Auth::user()->hasAnyRole(['Superadmin', 'Admin'])
                    ),
                
                // Ver detalles (todos los roles)
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->color('gray'),
                
                // Eliminar (solo Superadmin)
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => 
                        Auth::user()->hasRole('Superadmin')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => 
                            Auth::user()->hasRole('Superadmin')
                        ),
                        
                    Tables\Actions\BulkAction::make('mark_verified')
                        ->action(fn ($records) => $records->each->update([
                            'verified' => true,
                            'verified_on' => now(),
                            'status' => 'verified'
                        ]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->label('Marcar como verificados')
                        ->visible(fn (): bool => 
                            Auth::user()->hasAnyRole(['Superadmin', 'Admin'])
                        ),
                ]),
            ])
            ->defaultSort('payment_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => 
                Auth::user()->hasRole('Superadmin') 
                    ? $query 
                    : $query->where('tenant_id', Auth::user()->tenant_id)
            );
    }

    
     public static function getPages(): array
    {
        return [
            'index' =>ListPayments::route('/'),      // Plural aquí está bien
            'create' => CreatePayments::route('/create'),   // Singular
            'edit' => EditPayments::route('/{record}/edit'), // Singular
        ];
    }
}
