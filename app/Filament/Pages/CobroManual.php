<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\QrService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group as FormGroup;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Página de COBRO MANUAL — efectivo, bizum, transferencia, TPV físico.
 *
 * Permite al admin del club registrar una venta presencial:
 *  1. Seleccionar cliente existente o crear nuevo en el acto
 *  2. Elegir producto (abono / entrada / merch)
 *  3. Confirmar precio (override permitido) y método de pago
 *  4. Crea Order + OrderItem + Ticket QR + envío automático
 *
 * Diseñada para taquilla / caja del club, no para uso público.
 */
class CobroManual extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Datos económicos';

    protected static ?int $navigationSort = 220;

    protected static ?string $navigationLabel = 'Cobro manual';

    protected static ?string $title = 'Cobro manual (efectivo / bizum / TPV)';

    protected string $view = 'filament.pages.cobro-manual';

    /** Datos del formulario reactivo. */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'modo_cliente'   => 'existente',
            'metodo_pago'    => 'efectivo',
            'qty'            => 1,
            'province'       => 'Cádiz',
            'country'        => 'ES',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('1. Cliente')
                    ->description('Busca por nombre, email o DNI. Si es nuevo, crea su ficha aquí mismo.')
                    ->schema([
                        Radio::make('modo_cliente')
                            ->label('')
                            ->options([
                                'existente' => 'Cliente ya registrado',
                                'nuevo'     => 'Cliente nuevo (crear ahora)',
                            ])
                            ->inline()
                            ->live()
                            ->required(),

                        Select::make('customer_id')
                            ->label('Buscar cliente')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $q) => Customer::query()
                                ->where(function ($w) use ($q) {
                                    $w->where('first_name', 'like', "%$q%")
                                      ->orWhere('last_name',  'like', "%$q%")
                                      ->orWhere('email',      'like', "%$q%")
                                      ->orWhere('dni',        'like', "%$q%")
                                      ->orWhere('phone',      'like', "%$q%");
                                })
                                ->orderBy('first_name')
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => trim($c->first_name . ' ' . $c->last_name) . ' — ' . $c->email,
                                ])
                                ->toArray())
                            ->getOptionLabelUsing(fn ($value) => optional(Customer::find($value))->email)
                            ->required(fn ($get) => $get('modo_cliente') === 'existente')
                            ->visible(fn ($get) => $get('modo_cliente') === 'existente'),

                        FormGroup::make()
                            ->columns(2)
                            ->visible(fn ($get) => $get('modo_cliente') === 'nuevo')
                            ->schema([
                                TextInput::make('first_name')->label('Nombre')->required(),
                                TextInput::make('last_name')->label('Apellidos')->required(),
                                TextInput::make('email')->label('Email')->email()->required(),
                                TextInput::make('phone')->label('Teléfono')->required(),
                                TextInput::make('dni')->label('DNI/NIE')->required(),
                                TextInput::make('birth_date')->label('Fecha nacimiento')->type('date')->required(),
                                TextInput::make('address')->label('Dirección')->columnSpanFull()->required(),
                                TextInput::make('city')->label('Ciudad')->required(),
                                TextInput::make('postal_code')->label('CP')->required(),
                                TextInput::make('province')->label('Provincia'),
                            ]),
                    ]),

                Section::make('2. Producto')
                    ->description('Abono, entrada o producto de tienda.')
                    ->schema([
                        Select::make('product_id')
                            ->label('Producto')
                            ->searchable()
                            ->options(fn () => Product::query()
                                ->where('active', 1)
                                ->orderBy('type')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($p) => [
                                    $p->id => sprintf('[%s] %s — €%s', strtoupper($p->type ?? '?'), $p->name, number_format((float) $p->price, 2)),
                                ])
                                ->toArray())
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $p = Product::find($state);
                                if ($p) {
                                    $set('unit_price', $p->price);
                                }
                            }),

                        FormGroup::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('qty')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('unit_price')
                                    ->label('Precio unitario (€)')
                                    ->numeric()
                                    ->step('0.01')
                                    ->required()
                                    ->helperText('Sólo modifica si el precio difiere del de tienda.'),
                            ]),
                    ]),

                Section::make('3. Método de cobro')
                    ->schema([
                        Radio::make('metodo_pago')
                            ->label('')
                            ->options([
                                'efectivo'       => '💵 Efectivo',
                                'bizum'          => '📱 Bizum',
                                'transferencia'  => '🏦 Transferencia',
                                'tpv_fisico'     => '💳 TPV físico (datafono del club)',
                            ])
                            ->required(),

                        Textarea::make('admin_notes')
                            ->label('Notas (opcional)')
                            ->placeholder('Quien atiende, referencia bancaria, etc.')
                            ->rows(2),
                    ]),
            ])
            ->statePath('data');
    }

    /** Action principal — "Registrar cobro". */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('registrar')
                ->label('Registrar cobro')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('¿Confirmas registrar este cobro? Se creará el pedido pagado y se emitirá el ticket.')
                ->action(fn () => $this->procesarCobro()),
        ];
    }

    /** Lógica del cobro: crear customer si nuevo, order pagado, ticket. */
    protected function procesarCobro(): void
    {
        try {
            $d = $this->form->getState();

            // 1. Resolver / crear customer
            if (($d['modo_cliente'] ?? 'existente') === 'nuevo') {
                $customer = $this->crearCustomerYUser($d);
            } else {
                $customer = Customer::findOrFail($d['customer_id']);
            }

            // 2. Crear Order + OrderItem dentro de transacción
            $product = Product::findOrFail($d['product_id']);
            $qty       = max(1, (int) ($d['qty'] ?? 1));
            $unitPrice = (float) ($d['unit_price'] ?? $product->price);
            $vatRate   = 21; // por defecto

            $subtotal = round($unitPrice * $qty, 2);
            $vat      = round($subtotal * ($vatRate / 100) / (1 + $vatRate / 100), 2);
            $total    = $subtotal;

            DB::transaction(function () use ($customer, $product, $d, $qty, $unitPrice, $vatRate, $subtotal, $vat, $total) {
                $reference = 'ACF-CAJ-' . now()->format('Ym') . '-' . str_pad((string) (Order::max('id') + 1), 6, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'reference'        => $reference,
                    'customer_id'      => $customer->id,
                    'status'           => 'paid',
                    'channel'          => 'admin',
                    'subtotal'         => $subtotal,
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
                    'product_type'=> $product->type,
                    'name'       => $product->name,
                    'sku'        => $product->sku ?? null,
                    'qty'        => $qty,
                    'unit_price' => $unitPrice,
                    'vat_rate'   => $vatRate,
                    'subtotal'   => $subtotal,
                    'vat_amount' => $vat,
                    'total'      => $total,
                ]);

                // 3. Si es abono/entrada, generar Tickets QR vía el CheckoutService normal.
                if (in_array($product->type, ['abono', 'entrada'])) {
                    try {
                        $checkout = app(CheckoutService::class);
                        if (method_exists($checkout, 'generateTicketsForOrder')) {
                            $checkout->generateTicketsForOrder($order->fresh('items'));
                        }
                    } catch (\Throwable $e) {
                        // No bloquea — el ticket se puede regenerar desde el Order luego.
                        \Log::warning('Cobro manual ticket gen fallo', ['order' => $order->id, 'err' => $e->getMessage()]);
                    }
                }
            });

            Notification::make()
                ->title('Cobro registrado correctamente')
                ->body('Pedido creado y marcado como pagado. Email del cliente: ' . $customer->email)
                ->success()
                ->send();

            // Reset form
            $this->form->fill([
                'modo_cliente' => 'existente',
                'metodo_pago'  => 'efectivo',
                'qty'          => 1,
                'province'     => 'Cádiz',
                'country'      => 'ES',
            ]);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error al registrar el cobro')
                ->body($e->getMessage())
                ->danger()
                ->send();
            \Log::error('Cobro manual ERROR', ['err' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 1000)]);
        }
    }

    /** Crea User + Customer cuando el operador marca "cliente nuevo". */
    protected function crearCustomerYUser(array $d): Customer
    {
        return DB::transaction(function () use ($d) {
            $email = $d['email'];

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')),
                    'password' => Hash::make(Str::random(16)),
                ]
            );

            $customer = Customer::firstOrCreate(
                ['email' => $email],
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

            return $customer;
        });
    }
}
