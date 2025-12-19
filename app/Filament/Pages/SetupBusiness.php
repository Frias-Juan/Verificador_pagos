<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\Tenant;
use App\Models\PaymentGateway;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SetupBusiness extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static string $view = 'filament.pages.setup-business';
    protected static ?string $title = 'Configuración de tu Negocio';
    
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        // Si el usuario ya tiene negocio, lo sacamos de aquí
        if (Auth::user()->tenant_id) {
            $this->redirect('/admin');
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del negocio')
                    ->description('Información básica del negocio.')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->label('Nombre del negocio')
                            ->placeholder('Ej: Mi Tienda C.A.')
                            ->required(),
                            
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección del negocio')
                            ->placeholder('Ej: Calle 123, Edificio San José...'), 
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Configuración de Pasarelas')
                    ->description('Configura al menos una pasarela para recibir pagos.')
                    ->schema([
                        Forms\Components\Repeater::make('initial_gateways') 
                            ->label('Pasarelas')
                            ->schema([
                                Forms\Components\Select::make('gateway_type') 
                                    ->label('Tipo')
                                    ->options([
                                        'PAGOMOVIL' => 'Pago Móvil',
                                        'ZELLE' => 'Zelle',
                                    ])
                                    ->live() 
                                    ->required(),

                                Forms\Components\Select::make('gateway_name') 
                                    ->label('Banco')
                                    ->options(Bank::pluck('name', 'name'))
                                    ->required()
                                    ->hidden(fn(Get $get) => $get('gateway_type') !== 'PAGOMOVIL')
                                    ->placeholder('Seleccione banco'),
                                
                                Forms\Components\TextInput::make('zelle_name') 
                                    ->label('Nombre de Cuenta')
                                    ->placeholder('Ej: John Doe')
                                    ->required()
                                    ->hidden(fn(Get $get) => $get('gateway_type') !== 'ZELLE'),

                            ])
                            ->minItems(1)
                            ->maxItems(5) 
                            ->addActionLabel('Añadir otra pasarela')
                            ->columns(3),
                    ])
            ])
            ->statePath('data');
    }

    public function save()
{
    $formData = $this->form->getState();
    $user = Auth::user();

    // 1. Crear el Tenant
    // Si falla aquí (ej: falta una columna), Laravel te dará el error real
    $tenant = Tenant::create([
        'business_name' => $formData['business_name'],
        'address'       => $formData['address'],
        'owner_id'      => $user->id,
    ]);

    // 2. Procesar las pasarelas
    if (!empty($formData['initial_gateways'])) {
        foreach ($formData['initial_gateways'] as $gatewayData) {
            
            $nombreParaDB = $gatewayData['gateway_type'] === 'PAGOMOVIL' 
                            ? ($gatewayData['gateway_name'] ?? 'S/N') 
                            : ($gatewayData['zelle_name'] ?? 'S/N');

            $newGateway = \App\Models\PaymentGateway::create([
                'name'      => $nombreParaDB,
                'type'      => $gatewayData['gateway_type'],
                'is_active' => true,
            ]);

            // 3. Vincular en la tabla pivote
            // Asegúrate que en Tenant.php el método sea paymentGateways()
            $tenant->paymentGateways()->attach($newGateway->id);
        }
    }

    // 4. Vincular el usuario con el negocio
    // Usamos DB directo para asegurar que el cambio se guarde sin interferencias
    \Illuminate\Support\Facades\DB::table('users')
        ->where('id', $user->id)
        ->update(['tenant_id' => $tenant->id]);

    // 5. Vincular en tabla pivote de usuarios si existe
    if (method_exists($tenant, 'users')) {
        $tenant->users()->syncWithoutDetaching([$user->id]);
    }

    Notification::make()
        ->title('¡Configuración completada!')
        ->success()
        ->send();

    // Redirección directa
    return $this->redirect('/admin');
}
}