<div x-data="{ 
    open: false, 
    page: 0, 
    perPage: 5, 
    names: {{ json_encode($getState() ?? []) }},
    get totalPages() { return Math.ceil(this.names.length / this.perPage); },
    get paginatedNames() {
        let start = this.page * this.perPage;
        return this.names.slice(start, start + this.perPage);
    }
}" class="px-2 py-1">

    @if(count($getState() ?? []) > 0)
        {{-- Botón principal: Eliminado el border blanco en dark y mejorado el contraste --}}
        <button 
            @click="open = !open" 
            type="button"
            class="flex items-center gap-2 px-3 py-1 text-xs font-bold text-primary-600 bg-primary-50 rounded-full hover:bg-primary-100 transition dark:bg-gray-800 dark:text-primary-400"
        >
            <svg 
                class="w-3.5 h-3.5 transition-transform duration-300" 
                :class="{ 'rotate-180': open }" 
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
            <span>
            {{ count($getState()) }}
            {{ count($getState()) === 1 ?'Empleado' :'Empleados' }}
            </span>
        </button>

        <div x-show="open" x-collapse x-cloak>
            <div class="mt-2 ml-2 p-2 rounded-lg border 
                        bg-gray-100/80 border-gray-200 
                        dark:bg-white/5 dark:border-white/10">
                
                <div class="space-y-1">
                    <template x-for="(name, index) in paginatedNames" :key="index">
                        <div class="flex items-center gap-2 text-sm py-1 border-b last:border-0
                                    text-gray-800 border-gray-200
                                    dark:text-gray-100 dark:border-white/5">
                            <span class="text-primary-600 dark:text-primary-400 font-bold">•</span>
                            <span x-text="name" class="font-semibold text-[13px] tracking-tight"></span>
                        </div>
                    </template>
                </div>

                <template x-if="totalPages > 1">
                    <div class="flex items-center justify-between mt-3 pt-2 border-t 
                                border-gray-300 dark:border-white/10">
                        {{-- Texto de paginación más claro en dark --}}
                        <span class="text-[10px] uppercase font-black text-gray-500 dark:text-gray-300">
                            <span x-text="page + 1"></span> / <span x-text="totalPages"></span>
                        </span>
                        <div class="flex gap-1">
                            {{-- Botones de flecha: Ahora resaltan en gris claro/blanco en dark --}}
                            <button 
                                @click="page > 0 ? page-- : null" 
                                :disabled="page === 0"
                                type="button"
                                class="p-1 rounded border transition disabled:opacity-20
                                       bg-white border-gray-300 text-gray-800
                                       dark:bg-gray-600 dark:border-transparent dark:text-white dark:hover:bg-gray-500"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button 
                                @click="page < totalPages - 1 ? page++ : null" 
                                :disabled="page === totalPages - 1"
                                type="button"
                                class="p-1 rounded border transition disabled:opacity-20
                                       bg-white border-gray-300 text-gray-800
                                       dark:bg-gray-600 dark:border-transparent dark:text-white dark:hover:bg-gray-500"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @else
        <div class="px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400 italic font-medium">
            Sin empleados
        </div>
    @endif
</div>