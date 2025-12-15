<x-filament-panels::page>
<div class="fi-page-content-wrapper">
        <x-filament::section>
            <x-slot name="heading">
                Verificación de Pagos
            </x-slot>

            <form wire:submit="searchPayment" class="space-y-6">
                {{ $this->form }}

                <x-filament::button type="submit" size="lg" icon="heroicon-s-magnifying-glass"
                class="justify-content: space-around text-center text-lg">
                    Buscar y Verificar 
                </x-filament::button>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
