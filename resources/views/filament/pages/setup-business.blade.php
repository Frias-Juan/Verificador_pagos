<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg"
                wire:target="save">
                <span wire:loading.remove wire:target="save">
                    Finalizar Configuración
                </span>
                <span wire:loading wire:target="save">
                    Procesando...
                </span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>