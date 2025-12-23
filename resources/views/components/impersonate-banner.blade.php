@if(app(\Lab404\Impersonate\Services\ImpersonateManager::class)->isImpersonating())
    <div style="background-color: #756effff; color: white; padding: 10px; text-align: center; font-weight: 500; width: 100%; position: sticky; top: 0; z-index: 50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: center; gap: 1rem;">
            <span style="display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Estás en el perfil de:<strong>{{ auth()->user()->name }}</strong>
            </span>
            
            <a href="{{ route('impersonate.leave') }}" 
               style="background-color: #2b313aff; color: #f3f4f6; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; border: 1px solid #374151; transition: all 0.2s;"
               onmouseover="this.style.backgroundColor='#111827'"
               onmouseout="this.style.backgroundColor='#1f2937'">
                VOLVER A MI SESIÓN
            </a>
        </div>
    </div>
@endif