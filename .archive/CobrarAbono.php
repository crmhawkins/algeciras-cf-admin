<?php

namespace App\Filament\Pages;

use App\Mail\BienvenidaClienteMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group as FormGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Página COBRAR ABONO — flujo rápido para taquilla.
 *
 * Dos modos:
 *  1) RENOVACIÓN: el operador introduce nº de socio o DNI; el sistema
 *     localiza al Customer y precarga sus datos. Solo escoge el abono y
 *     método de cobro.
 *  2) NUEVO: el operador rellena los datos del cliente. Se crea User +
 *     Customer + se envía email con credenciales temporales y luego email
 *     normal de confirmación con QR del abono.
 *
 * En AMBOS casos:
 *  - Se genera Order(status=paid, channel=admin) + OrderItem + Ticket QR
 *  - Se mandan los emails que correspondan
 */
class CobrarAbono extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Datos económicos';

    protected static ?int $navigationSort = 230;

    protected static ?string $navigationLabel = 'Cobrar abono';

    protected static ?string $title = 'Cobrar abono · Renovación o nuevo';

    protected string $view = 'filament.pages.cobrar-abono';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'modo'           => 'renovacion',
            'metodo_pago'    => 'efectivo',
            'province'       => 'Cádiz',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tipo de venta')
                    ->schema([
                        Radio::make('modo')
                            ->label('')
                            ->options([
                                'renovacion' => '🔁 Renovación de abono (cliente existente)',
                                'nuevo'      => '✨ Cliente nuevo',
                            ])
                            ->inline()
                            ->live()
                            ->required(),
                    ]),

                // --- RENOVACIÓN ---
                Section::make('Buscar abonado')
                    ->description('Introduce su número de socio o DNI; los datos se cargarán automáticamente.')
                    ->visible(fn (Get $get) => $get('modo') === 'renovacion')
                    ->schema([
                        TextInput::make('socio_busqueda')
                            ->label('Nº de socio o DNI')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) return;
                                $c = Customer::query()
                                    ->where('socio_number', $state)
                                    ->orWhere('dni', strtoupper($state))
                                    ->orWhere('dni', $state)
                                    ->first();
                                if ($c) {
                                    $set('customer_id',  $c->id);
                                    $set('preview_name', trim($c->first_name . ' ' . $c->last_name));
                                    $set('preview_email',$c->email);
                                    $set('preview_dni',  $c->dni);
                                } else {
                                    $set('customer_id',  null);
                                    $set('preview_name', null);
                                }
                            }),

                        TextInput::make('preview_name')->label('Cliente encontrado')->disabled()->dehydrated(false),
                        TextInput::make('preview_email')->label('Email')->disabled()->dehydrated(false),
                        TextInput::make('preview_dni')->label('DNI')->disabled()->dehydrated(false),
                        TextInput::make('customer_id')->hidden()->dehydrated(),
                    ]),

                // --- NUEVO ---
                Section::make('Datos del cliente nuevo')
                    ->description('Se creará su cuenta y se le enviará un email con la contraseña.')
                    ->visible(fn (Get $get) => $get('modo') === 'nuevo')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')->label('Nombre')->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('last_name')->label('Apellidos')->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('email')->label('Email')->email()->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('phone')->label('Teléfono')->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('dni')->label('DNI/NIE')->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('birth_date')->label('Fecha nacimiento')->type('date')->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('address')->label('Dirección')->columnSpanFull()->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('city')->label('Ciudad')->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('postal_code')->label('CP')->required(fn (Get $get) => $get('modo') === 'nuevo'),
                        TextInput::make('province')->label('Provincia'),
                    ]),

                Section::make('Abono y precio')
                    ->schema([
                        Select::make('product_id')
                            ->label('Abono')
                            ->required()
                            ->searchable()
                            ->live()
                            ->options(fn () => Product::where('type', 'abono')
                                ->where('active', 1)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => $p->name . ' — €' . number_format((float) $p->price, 2)])
                                ->toArray())
                            ->afterStateUpdated(function ($state, callable $set) {
                                $p = Product::find($state);
                                if ($p) $set('precio', $p->price);
                            }),
                        TextInput::make('precio')
                            ->label('Precio final (€)')
                            ->numeric()
                            ->step('0.01')
                            ->required()
                            ->helperText('Sólo modifica si aplicas descuento/recargo.'),
                    ]),

                Section::make('Método de cobro')
                    ->schema([
                        Radio::make('metodo_pago')
                            ->label('')
                            ->options([
                                'efectivo'      => '💵 Efectivo',
                                'bizum'         => '📱 Bizum',
                                'transferencia' => '🏦 Transferencia',
                                'tpv_fisico'    => '💳 TPV físico',
                            ])
                            ->required(),
                        Textarea::make('admin_notes')->label('Notas (opcional)')->rows(2),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cobrar')
                ->label('Cobrar abono y enviar email')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Se generará el pedido pagado, se emitirá el ticket con QR y se mandará el email al cliente.')
                ->action(fn () => $this->procesar()),
        ];
    }

    protected function procesar(): void
    {
        try {
            $d = $this->form->getState();
            $product = Product::findOrFail($d['product_id']);

            // 1. Resolver / crear customer
            [$customer, $passwordTemporal] = $this->resolverCustomer($d);

            // 2. Crear order pagado + ticket QR
            $unit  = (float) ($d['precio'] ?? $product->price);
            $total = round($unit, 2);
            $vat   = round($total * 0.21 / 1.21, 2);

            $orderId = DB::transaction(function () use ($customer, $product, $d, $unit, $total, $vat) {
                $reference = 'ACF-ABO-' . now()->format('Ym') . '-' . str_pad((string) (Order::max('id') + 1), 6, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'reference'        => $reference,
                    'customer_id'      => $customer->id,
                    'status'           => 'paid',
                    'channel'          => 'admin',
                    'subtotal'         => $total,
                    'vat'              => $vat,
                    'shipping_cost'    => 0,
                    'gestion_fee'      => 0,
                    'total'            => $total,
                    'currency'         => 'EUR',
                    'payment_gateway'  => $d['metodo_pago'] ?? 'efectivo',
                    'payment_intent_id'=> 'manual-' . Str::uuid()->toString(),
                    'paid_at'          => now(),
                    'admin_notes'      => $d['admin_notes'] ?? null,
                ]);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'product_type'=> 'abono',
                    'name'       => $product->name,
                    'qty'        => 1,
                    'unit_price' => $unit,
                    'vat_rate'   => 21,
                    'subtotal'   => $total,
                    'vat_amount' => $vat,
                    'total'      => $total,
                ]);

                return $order->id;
            });

            // 3. Ticket QR + email confirmación (lo hace CheckoutService)
            try {
                $checkout = app(CheckoutService::class);
                $order = Order::with('items')->find($orderId);
                if ($order && method_exists($checkout, 'generateTicketsForOrder')) {
                    $checkout->generateTicketsForOrder($order);
                }
            } catch (\Throwable $e) {
                Log::warning('CobrarAbono ticket gen warning', ['err' => $e->getMessage()]);
            }

            // 4. Si es nuevo, además email de bienvenida con credenciales
            if ($passwordTemporal) {
                try {
                    Mail::to($customer->email)->send(new BienvenidaClienteMail($customer, $passwordTemporal, 'abono'));
                } catch (\Throwable $e) {
                    Log::warning('CobrarAbono welcome mail warning', ['err' => $e->getMessage()]);
                }
            }

            Notification::make()
                ->title('Abono registrado y email enviado')
                ->body(($passwordTemporal ? 'Cliente nuevo creado. ' : 'Renovación. ')
                    . 'Email enviado a ' . $customer->email)
                ->success()
                ->duration(10000)
                ->send();

            $this->form->fill(['modo' => 'renovacion', 'metodo_pago' => 'efectivo', 'province' => 'Cádiz']);
        } catch (\Throwable $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
            Log::error('CobrarAbono ERROR', ['err' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 800)]);
        }
    }

    /**
     * Devuelve [$customer, $passwordTemporal|null]. Password se devuelve sólo
     * si se acaba de crear el customer (cliente nuevo).
     *
     * @return array{0: Customer, 1: ?string}
     */
    protected function resolverCustomer(array $d): array
    {
        if (($d['modo'] ?? 'renovacion') === 'renovacion') {
            $id = $d['customer_id'] ?? null;
            if (! $id) {
                throw new \RuntimeException('No se ha localizado al abonado. Verifica el nº de socio o DNI.');
            }
            return [Customer::findOrFail($id), null];
        }

        // Nuevo: crear User + Customer + password aleatoria
        $passwordPlain = $this->generarPasswordAmigable();

        $customer = DB::transaction(function () use ($d, $passwordPlain) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name'     => trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')),
                    'password' => Hash::make($passwordPlain),
                ]
            );

            return Customer::firstOrCreate(
                ['email' => $d['email']],
                [
                    'user_id'     => $user->id,
                    'first_name'  => $d['first_name'],
                    'last_name'   => $d['last_name'],
                    'phone'       => $d['phone'],
                    'dni'         => $d['dni'],
                    'birth_date'  => $d['birth_date'],
                    'address'     => $d['address'],
                    'city'        => $d['city'],
                    'postal_code' => $d['postal_code'],
                    'province'    => $d['province'] ?? 'Cádiz',
                    'country'     => 'ES',
                ]
            );
        });

        return [$customer, $passwordPlain];
    }

    /** Genera "Algeciras2026!XX4Z" tipo password recordable. */
    protected function generarPasswordAmigable(): string
    {
        return 'Algeciras' . now()->year . '!' . strtoupper(Str::random(4));
    }
}
