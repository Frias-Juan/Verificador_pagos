<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Payment;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon; // Usar Carbon para la fecha de verificación

class VerifyPaymentPage extends Page implements HasActions
{
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static string $view = 'filament.pages.verify-payment-page';
    protected static ?string $navigationLabel = 'Verificar Pago';
    protected static ?string $title = 'Verificación Rápida de Pagos';
    protected static ?string $slug = 'verificar-pago';
    
    protected static ?string $navigationGroup = 'Pagos';

    // ⚠️ Permitir solo a empleados y admins
    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Superadmin', 'Admin', 'Employee']);
    }

    // --- PROPIEDADES DE LIVEWIRE/FORM ---
    public ?array $data = [];
    public ?Payment $foundPayment = null;

    public function mount(): void
    {
        // Inicializar el formulario y el pago encontrado
        $this->form->fill();
        $this->foundPayment = null;
    }
    
    // --- DEFINICIÓN DEL FORMULARIO ---
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('reference_end')
                    ->label('Últimos 6 Dígitos de la Referencia')
                    ->placeholder('Ej: 1234')
                    ->required()
                    ->minLength(4)
                    ->maxLength(4)
                    ->mask('9999') 
                    ->numeric(),

                TextInput::make('amount')
                    ->label('Monto Exacto')
                    ->placeholder('Ej: 150.50')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->inputMode('decimal'),
            ])
            ->statePath('data');
    }

    // --- ACCIONES DE LA PÁGINA (Búsqueda y Verificación) ---
    protected function getActions(): array
    {
        return [
            // ACCIÓN 1: BÚSQUEDA DEL PAGO (Siempre visible)
            Action::make('searchPayment')
                ->label('Buscar Pago Pendiente')
                ->color('primary')
                ->icon('heroicon-o-magnifying-glass')
                ->action('searchPayment'), // Llama al método searchPayment()
            
            // ACCIÓN 2: VERIFICACIÓN (Solo visible si $foundPayment no es nulo)
            Action::make('verify')
                ->label('Verificar Pago')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->modalHeading('Confirmar Verificación')
                ->modalDescription('¿Estás seguro de que deseas marcar este pago como verificado? Esta acción no se puede deshacer fácilmente.')
                ->modalSubmitActionLabel('Sí, Verificar Pago')
                ->requiresConfirmation()
                // ⚠️ Se hace visible solo si el pago fue encontrado
                ->visible($this->foundPayment !== null) 
                ->action(function () {
                    if ($this->foundPayment) {
                        $this->foundPayment->update([
                            'verified' => true,
                            'verified_on' => Carbon::now(),
                            'status' => 'verified',
                        ]);

                        Notification::make()
                            ->title('¡Verificado con Éxito!')
                            ->success()
                            ->body("El pago #{$this->foundPayment->id} ha sido marcado como verificado.")
                            ->send();

                        // Limpiar y resetear el estado después de verificar
                        $this->foundPayment = null;
                        $this->form->fill();
                    }
                }),
        ];
    }
    
    // --- MÉTODO LIVEWIRE PARA BUSCAR PAGO ---
    public function searchPayment(): void
    {
        try {
            // 1. Validar datos
            $validatedData = $this->form->getState();
            $referenceEnd = $validatedData['reference_end'];
            $amount = $validatedData['amount'];
            $tenantId = Auth::user()->tenant_id;

            if (!$tenantId) {
                 Notification::make()
                    ->title('Error de Seguridad')
                    ->danger()
                    ->body('Tu usuario no está asociado a ningún negocio (Tenant).')
                    ->send();
                return;
            }

            // 2. Ejecutar la búsqueda con Multitenencia y criterios
            $payment = Payment::query()
                ->where('tenant_id', $tenantId)
                ->where('verified', false)
                ->whereRaw('RIGHT(`reference`, 4) = ?', [$referenceEnd]) 
                ->where('amount', $amount)
                ->pending() // Asumiendo que el scope pending() filtra por status = 'pending'
                ->first();

            // 3. Actualizar la propiedad foundPayment
            $this->foundPayment = $payment;

            // 4. Notificación
            if ($this->foundPayment) {
                Notification::make()
                    ->title('¡Pago Encontrado!')
                    ->success()
                    ->body("Pago #{$this->foundPayment->id} de {$this->foundPayment->remitter} encontrado. Monto: \${$this->foundPayment->amount}.")
                    ->send();
            } else {
                Notification::make()
                    ->title('No Encontrado')
                    ->warning()
                    ->body("No se encontró ningún pago pendiente con esos criterios.")
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error en la Búsqueda')
                ->danger()
                ->body('Ocurrió un error al procesar la solicitud. Intenta de nuevo.')
                ->send();
            // dd($e->getMessage()); // Descomentar para depuración
        }
    }
}