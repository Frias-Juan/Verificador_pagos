<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\Tenant;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SetupBusiness extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static string $view = 'filament.pages.setup-business';
    protected static ?string $title = 'Configuración de tu Negocio';
    
    // Ocultar de la navegación lateral para que no puedan salir sin terminar
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        // Si ya tiene un negocio, lo mandamos al dashboard
        if (Auth::user()->tenant_id) {
            redirect()->to('/admin');
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos del negocio')
                    ->schema([
                        TextInput::make('business_name')
                            ->label('Nombre del negocio')
                            ->required(),
                        Textarea::make('address')
                            ->label('Dirección del negocio'),
                    ])->columns(2),

                Section::make('Configuración de Pasarelas de Pago Inicial')
                    ->schema([
                        Repeater::make('initial_gateways')
                            ->label('Añadir Pasarela de Pago')
                            ->schema([
                                Select::make('gateway_type')
                                    ->label('Tipo de Pasarela')
                                    ->options([
                                        'PAGOMOVIL' => 'Pago Móvil',
                                        'ZELLE' => 'Zelle',
                                    ])->live()->required(),

                                Select::make('gateway_name')
                                    ->label('Nombre del Banco')
                                    ->options(Bank::pluck('name', 'name'))
                                    ->required()
                                    ->hidden(fn($get) => $get('gateway_type') !== 'PAGOMOVIL'),

                                TextInput::make('zelle_name')
                                    ->label('Nombre de la Cuenta (Zelle)')
                                    ->required()
                                    ->hidden(fn($get) => $get('gateway_type') !== 'ZELLE'),
                            ])->minItems(1)->columns(3),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();
        $user = Auth::user();

        // 1. Crear el Tenant (Negocio)
        $tenant = Tenant::create([
            'name' => $formData['business_name'],
            'address' => $formData['address'],
            // Aquí guardas los demás campos según tu modelo Tenant
        ]);

        // 2. Guardar Pasarelas (Lógica según tu estructura de BD)
        foreach ($formData['initial_gateways'] as $gateway) {
            $tenant->gateways()->create($gateway); 
        }

        // 3. Vincular Usuario con el Negocio
        $user->update([
            'tenant_id' => $tenant->id,
            'status' => 'approved', // Ya está totalmente listo
        ]);

        Notification::make()
            ->title('¡Negocio configurado!')
            ->success()
            ->send();

        redirect()->to('/admin');
    }
}