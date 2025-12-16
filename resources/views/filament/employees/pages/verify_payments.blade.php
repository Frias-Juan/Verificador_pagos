<x-filament-panels::page>
<div class="fi-page-content-wrapper">
        <x-filament::section>
            <x-slot name="heading">
                Verificación de Pagos
            </x-slot>

            <form wire:submit="searchPayment" class="space-y-6">
                {{ $this->form }}

                <x-filament::button type="submit" size="lg" icon="heroicon-s-magnifying-glass"
                wire:loading.attr="disabled"
                class="justify-content: space-around text-center text-lg">
                   <span wire:loading.remove wire:target="searchPayment">
                        Buscar y Verificar
                    </span>
                    <span wire:loading wire:target="searchPayment">
                        <x-filament::loading-indicator class="h-5 w-5 inline mr-2" />
                        Verificando...
                    </span>
                </x-filament::button>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
