<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seat;
use App\Models\Sector;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\QrService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Actions\Action as FormAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Support\Enums\Width;
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

    /** Tipo de producto al que se filtra el select (?tipo=abono|entrada|merch). Null = todos. */
    public ?string $tipoProductoFiltro = null;

    /** Variante del producto (solo aplica a abonos): 'renovacion' | 'nuevo' | null. */
    public ?string $varianteProductoFiltro = null;

    /** Métodos de pago permitidos. Si null, se ofrecen los 4 (efectivo/bizum/transferencia/tpv). */
    public ?array $metodosPagoPermitidos = null;

    public function mount(): void
    {
        // Leemos query params para atajos del escritorio:
        //   ?modo=nuevo|existente   → pre-selecciona modo cliente
        //   ?tipo=abono|entrada|merch → filtra el dropdown de productos
        $modoParam = request()->query('modo');
        $tipoParam = request()->query('tipo');

        $modo = in_array($modoParam, ['nuevo', 'existente']) ? $modoParam : 'existente';
        $this->tipoProductoFiltro = in_array($tipoParam, ['abono', 'entrada', 'merch']) ? $tipoParam : null;

        $this->form->fill([
            'modo_cliente'   => $modo,
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
                            ->helperText('Por nombre, apellidos, email, DNI, teléfono o número de abonado/socio.')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $isNum = ctype_digit(trim($search));
                                return Customer::query()
                                    ->where(function ($w) use ($search, $isNum) {
                                        $w->where('first_name', 'like', "%{$search}%")
                                          ->orWhere('last_name',  'like', "%{$search}%")
                                          ->orWhere('email',      'like', "%{$search}%")
                                          ->orWhere('dni',        'like', "%{$search}%")
                                          ->orWhere('phone',      'like', "%{$search}%");
                                        if ($isNum) {
                                            $w->orWhere('socio_number',    (int) $search)
                                              ->orWhere('legacy_socio_id', (int) $search)
                                              ->orWhere('legacy_user_id',  (int) $search);
                                        }
                                    })
                                    ->orderByRaw('CASE WHEN socio_number IS NOT NULL THEN 0 ELSE 1 END')
                                    ->orderBy('first_name')
                                    ->limit(30)
                                    ->get()
                                    ->mapWithKeys(function ($c) {
                                        $tag = $c->socio_number ? "Socio #{$c->socio_number} — " : '';
                                        return [
                                            $c->id => $tag . trim($c->first_name . ' ' . $c->last_name) . ' — ' . $c->email,
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $c = Customer::find($value);
                                if (!$c) return '';
                                $tag = $c->socio_number ? "Socio #{$c->socio_number} — " : '';
                                return $tag . trim($c->first_name . ' ' . $c->last_name) . ' — ' . $c->email;
                            })
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
                                ->when($this->tipoProductoFiltro, fn ($q, $tipo) => $q->where('type', $tipo))
                                ->when($this->varianteProductoFiltro === 'renovacion', fn ($q) => $q->where(function ($w) {
                                    $w->where('name', 'like', '%enov%'); // Renov / Renovación
                                }))
                                ->when($this->varianteProductoFiltro === 'nuevo', fn ($q) => $q->where(function ($w) {
                                    $w->where('name', 'like', '%uevo%')   // Nuevo / nuevo
                                      ->orWhere(function ($x) {
                                          // También productos sin variante explícita (ej. Palco VIP) — solo
                                          // los que no tengan "renov" en el nombre.
                                          $x->where('name', 'not like', '%enov%');
                                      });
                                }))
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

                        // --- ASIENTO (solo abonos) — campos hidden + placeholder
                        //     con resumen. El modal se monta en el blade con Alpine.js
                        //     para abrir el plano con un click.
                        FormGroup::make()
                            ->visible(fn () => $this->tipoProductoFiltro === 'abono')
                            ->schema([
                                Hidden::make('sector_id')->extraAttributes(['id' => 'cm-sector-id']),
                                Hidden::make('seat_id')->required()->extraAttributes(['id' => 'cm-seat-id']),

                                Placeholder::make('btn_elegir_butaca')
                                    ->hiddenLabel()
                                    ->content(fn () => new \Illuminate\Support\HtmlString(
                                        '<button type="button"'
                                        . ' onclick="window.dispatchEvent(new CustomEvent(\'cm-open-estadio-modal\'))"'
                                        . ' style="display:inline-flex;align-items:center;gap:12px;'
                                        . ' padding:14px 28px;border-radius:12px;border:0;'
                                        . ' background:#dc2626;color:#fff;font-weight:700;font-size:16px;'
                                        . ' box-shadow:0 4px 14px rgba(220,38,38,.35);cursor:pointer;'
                                        . ' transition:background .15s,transform .15s,box-shadow .15s;"'
                                        . ' onmouseover="this.style.background=\'#b91c1c\';this.style.transform=\'translateY(-1px)\';this.style.boxShadow=\'0 6px 18px rgba(220,38,38,.5)\'"'
                                        . ' onmouseout="this.style.background=\'#dc2626\';this.style.transform=\'\';this.style.boxShadow=\'0 4px 14px rgba(220,38,38,.35)\'">'
                                        . '🏟️ Elegir butaca en el plano del estadio'
                                        . '</button>'
                                    )),

                                Placeholder::make('asiento_elegido')
                                    ->label('Asiento elegido')
                                    ->content(function ($get) {
                                        $seatId = $get('seat_id');
                                        if (!$seatId) return 'Aún no se ha elegido butaca. Pulsa "Elegir butaca" arriba.';
                                        $seat = \App\Models\Seat::with('sector')->find($seatId);
                                        if (!$seat) return 'Butaca no encontrada (id=' . $seatId . ')';
                                        return "🪑 " . ($seat->sector?->name ?? 'Sector ?') . " · Fila {$seat->row} · Butaca {$seat->number}";
                                    })
                                    ->extraAttributes(['id' => 'cm-asiento-display']),
                            ]),
                    ]),

                Section::make('3. Método de cobro')
                    ->schema([
                        Radio::make('metodo_pago')
                            ->label('')
                            ->options(function () {
                                $todos = [
                                    'efectivo'       => '💵 Efectivo',
                                    'bizum'          => '📱 Bizum',
                                    'transferencia'  => '🏦 Transferencia',
                                    'tpv_fisico'     => '💳 TPV físico (datafono del club)',
                                ];
                                if (!$this->metodosPagoPermitidos) return $todos;
                                return array_intersect_key($todos, array_flip($this->metodosPagoPermitidos));
                            })
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

                    // 4. Si es abono y el operador asignó butaca, vincular y marcar
                    //    el seat como 'sold' para que no se reutilice.
                    if ($product->type === 'abono' && !empty($d['seat_id'])) {
                        $seat = Seat::find($d['seat_id']);
                        if ($seat && $seat->status === 'free') {
                            $ticketsCreados = Ticket::where('order_item_id', '!=', null)
                                ->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))
                                ->get();
                            if ($ticketsCreados->isEmpty()) {
                                // Fallback: buscar tickets por customer_id + product_id recién creados.
                                $ticketsCreados = Ticket::where('customer_id', $customer->id)
                                    ->where('product_id', $product->id)
                                    ->where('created_at', '>=', $order->created_at)
                                    ->get();
                            }
                            foreach ($ticketsCreados as $t) {
                                $t->seat_id = $seat->id;
                                $t->save();
                            }
                            $seat->status = 'sold';
                            $seat->save();
                        }
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
