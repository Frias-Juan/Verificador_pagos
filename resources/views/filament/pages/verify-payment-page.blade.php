<x-filament-panels::page>
    <div class="space-y-6 max-w-4xl mx-auto">
        
        <x-filament::section>
            <x-slot name="heading">
                🔎 Búsqueda de Pago Pendiente
            </x-slot>
            
            <p class="text-sm text-gray-500 mb-4">
                Ingrese los últimos 4 dígitos de la referencia y el monto exacto para localizar y verificar el pago.
            </p>
            
            {{ $this->form }}
            
            </x-filament::section>

        @if ($foundPayment)
            <x-filament::section class="border border-success-400 bg-success-50">
                <x-slot name="heading">
                    ✅ Pago Encontrado: #{{ $foundPayment->id }}
                </x-slot>
                
                <div class="grid grid-cols-4 gap-4">
                    
                    <div class="col-span-2">
                        <p class="font-bold text-gray-500">Monto del Pago:</p>
                        <p class="text-3xl font-extrabold text-success-600">${{ number_format($foundPayment->amount, 2) }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="font-bold text-gray-500">Referencia Completa:</p>
                        <p class="text-xl font-mono">{{ $foundPayment->reference }}</p>
                    </div>

                    <div class="col-span-2 pt-2 border-t">
                        <p class="font-bold text-gray-500">Pagador:</p>
                        <p class="text-base">{{ $foundPayment->remitter }}</p>
                    </div>
                    <div class="col-span-2 pt-2 border-t">
                        <p class="font-bold text-gray-500">Fecha de Transferencia:</p>
                        <p class="text-base">{{ $foundPayment->payment_date->format('d/m/Y') }}</p>
                    </div>
                    
                </div>

                <div class="flex justify-end mt-6">
                    {{ $this->verify }}
                </div>
            </x-filament::section>
        
        @elseif (array_key_exists('reference_end', $this->data ?? []) && $foundPayment === null)
            <x-filament::section class="border border-warning-500 bg-warning-50">
                <p class="text-center font-bold text-warning-700">
                    ⚠️ No se encontró ningún pago pendiente que coincida con la referencia y el monto. Por favor, revisa los datos.
                </p>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>