<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentsResource\Pages;
use App\Models\Payment;
use Filament\Tables\Actions\Action;
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

    /**
     * El formulario se mantiene por si decides usar un ViewAction (Solo lectura)
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
           
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                
                // Mostramos el nombre del negocio solo al Superadmin
                Tables\Columns\TextColumn::make('tenant.business_name')
                    ->label('Negocio')
                    ->visible(fn() => auth()->user()->hasRole('Superadmin')),

                Tables\Columns\TextColumn::make('payment_gateway.type')
                    ->label('Pasarela'),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto Bs')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('remitter')
                    ->label('Emisor')
                    ->state(function (Payment $record): string {
        return $record->remitter ?: ($record->phone_number ?: 'Sin identificar');
    })
    ->description(fn (Payment $record) => $record->remitter ? $record->phone_number : null)
    ->searchable(['remitter', 'phone_number'])
    ->sortable()
    ->color(fn (Payment $record) => ($record->remitter || $record->phone_number) ? null : 'gray')
    ->placeholder('No provisto'),

                Tables\Columns\TextColumn::make('bank')
                    ->label('Banco'),
                
                Tables\Columns\TextColumn::make('payment_date')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('verified')
                    ->boolean()
                    ->label('Verificado'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('verified')
                    ->label('Filtrar por Verificación'),
            ])
            ->actions([
                // ACCIÓN PERSONALIZADA: VERIFICAR
                Action::make('verify')
                    ->label('Verificar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    // Coincide con el permiso de tu seeder
                    ->visible(fn (Payment $record) => !$record->verified && auth()->user()->can('verify_payments'))
                    ->action(function (Payment $record) {
                        $record->update([
                            'verified' => true,
                            'verified_on' => now(),
                            'status' => 'verified', // Asegúrate de tener esta columna o quitarla
                        ]);
                    }),//Action::databaseTransaction() ?

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('delete_payments::resource')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_payments::resource')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Ajuste de Query para que el Superadmin vea TODO
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasRole('Superadmin')) {
            return $query; // Sin restricciones
        }

        // Para los demás, Filament maneja el tenant_id automáticamente si el panel está configurado,
        // pero esto es un respaldo de seguridad:
        return $query->where('tenant_id', $user->tenant_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }

    // --- POLÍTICAS DE ACCESO ---

    public static function canViewAny(): bool {
        return auth()->user()->can('view_any_payments::resource');
    }

    public static function canCreate(): bool {
        return false; // Los pagos no se crean manualmente
    }

    public static function canEdit(Model $record): bool {
        return false; // Los pagos no se editan
    }

    public static function canDelete(Model $record): bool {
        return auth()->user()->can('delete_payments::resource');
    }
}