<div class="px-2">
    @php 
        // Obtenemos el array de nombres
        $names = $getState() ?? []; 
    @endphp

    @if(count($names) > 0)
        <div class="relative min-w-[160px]">
            <select 
                onchange="this.selectedIndex = 0"
                class="block w-full appearance-none px-3 py-1.5 pr-8 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 cursor-pointer dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600"
            >
                <option value="" disabled selected>
                    👤 {{ count($names) }} {{ count($names) === 1 ? 'Empleado' : 'Empleados' }}
                </option>
                
                @foreach($names as $name)
                    <option disabled class="text-gray-900">
                        • {{ $name }}
                    </option>
                @endforeach
            </select>
            
            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
    @else
        <span class="text-xs text-gray-400 italic px-3">Sin empleados</span>
    @endif
</div>