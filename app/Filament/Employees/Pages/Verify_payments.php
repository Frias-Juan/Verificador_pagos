<?php

namespace App\Filament\Employees\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class Verify_payments extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.employees.pages.verify_payments';
    protected static ?string $navigationLabel = 'Verificación de Pagos';
    protected static ?string $modelLabel = 'Verificación de Pago';
    protected static ?string $pluralModelLabel = 'Verificación de Pagos';
    protected static ?string $title = 'Verificación de Pagos';

    protected static ?string $slug = '/'; 

    public ?string $reference = null;
    public ?float $amount = null;

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('reference')
                ->label('Referencia de Pago')
                ->required()
                ->helperText('Ingrese los últimos 6 dígitos de la referencia.'),

            TextInput::make('amount')
                ->label('Monto (Bs)')
                ->required()
                ->numeric()
                ->helperText('Ingrese el monto del pago.'),
        ];
    }

    public function searchPayment(): void
{
    try {
        // 1. Validar el formulario
        $data = $this->form->getState();

        // 2. Lógica de Búsqueda
        $payment = Payment::where(DB::raw('RIGHT(reference, 6)'), $data['reference'])
            ->where('amount', $data['amount'])
            ->first();

        // 3. Manejo de Casos y Notificaciones
        
        if (is_null($payment)) {
            // Caso 1: Pago No Encontrado
            Notification::make()
                ->title('❌ Pago No Encontrado')
                ->body('No se encontró ningún pago que coincida con la referencia y el monto proporcionados.')
                ->danger()
                ->send();
            
        } elseif ($payment->verified) {
            // Caso 2: Pago Encontrado, pero YA Verificado
            Notification::make()
                ->title('⚠️ Pago YA Verificado')
                ->body('El pago con la referencia ' . $data['reference'] . ' fue verificado previamente.')
                ->warning()
                ->send();

        } else {
            // Caso 3: Pago Encontrado y Pendiente de Verificación (Proceder a verificar)

            // 3.1. Realizar la verificación (Actualizar la columna en la base de datos)
            // ⚠️ Asume que tienes una columna 'verified' (booleana) en tu tabla Payment
            $payment->verified = true; 
            $payment->verified_on = now(); // Usar `verified_at` para timestamps
            $payment->save();

            Notification::make()
                ->title('✅ ¡Pago Verificado!')
                ->body('¡El pago con referencia ' . $data['reference'] . ' ha sido VERIFICADO con éxito!')
                ->success()
                ->send();
        }

    } catch (\Throwable $e) {
        // En caso de errores inesperados (p. ej., problemas de conexión a DB)
        Notification::make()
            ->title('Error Interno')
            ->body('Ocurrió un error al procesar la solicitud: ' . $e->getMessage())
            ->danger()
            ->send();
        return;
    }
    
    // 4. Limpiar los campos después de la operación, usando reset() de Livewire
    $this->form->fill([
        'reference' => null,
        'amount' => null,
    ]);
}
}
