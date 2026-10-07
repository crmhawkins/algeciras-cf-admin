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

    /** Abono resuelto automáticamente (flujo alta/renovación): ['id','name','price']. */
    public ?array $autoAbono = null;

    /** sector.zone (string interno) -> zone_id del producto de abono. */
    protected function zoneIdFromSectorZone(?string $z): ?int
    {
        if (!$z) return null;
        return match (true) {
            str_contains($z, 'tribuna')   => 1,
            str_contains($z, 'preferent') => 2,
            str_contains($z, 'fondo')     => 3,
            str_contains($z, 'palco')     => 5,
            default                       => 3,
        };
    }

    /**
     * Resuelve el producto de abono automáticamente según la zona del asiento
     * (o, en su defecto, la del último ticket del cliente) + la variante
     * (nuevo / renovación) y la temporada actual. Así el operador no tiene que
     * elegir producto ni precio en los flujos de abono.
     */
    protected function resolverAbonoAuto(?int $seatId, ?int $customerId): ?Product
    {
        if ($this->tipoProductoFiltro !== 'abono' || !$this->varianteProductoFiltro) {
            return null;
        }

        $zoneId = null;
        if ($seatId) {
            $seat = Seat::with('sector')->find($seatId);
            $zoneId = $this->zoneIdFromSectorZone($seat?->sector?->zone);
        }
        if (!$zoneId && $customerId) {
            $zoneId = Ticket::where('customer_id', $customerId)
                ->whereNotNull('zone_id')->orderByDesc('id')->value('zone_id');
        }
        if (!$zoneId) {
            return null;
        }

        $seasonId = \App\Models\Season::current()?->id;
        $kw = $this->varianteProductoFiltro === 'renovacion' ? '%enov%' : '%uevo%';

        return Product::where('type', 'abono')->where('season_id', $seasonId)->where('zone_id', $zoneId)
            ->where('name', 'like', $kw)->first()
            ?? Product::where('type', 'abono')->where('season_id', $seasonId)->where('zone_id', $zoneId)->first();
    }

    public function mount(): void
    {
        // Query params soportados (vienen de VentaAbonos y atajos):
        //   ?modo=nuevo|existente        → pre-selecciona modo cliente
        //   ?tipo=abono|entrada|merch    → filtra el dropdown de productos
        //   ?variante=renovacion|nuevo   → filtra abonos por nombre (variante)
        //   ?customer_id=N               → pre-selecciona cliente existente
        //   ?sector_id=N                 → pre-selecciona sector para el modal de butaca
        //   ?seat_id=N                   → pre-selecciona la butaca (viene de Nuevas altas)
        $modoParam     = request()->query('modo');
        $tipoParam     = request()->query('tipo');
        $varianteParam = request()->query('variante');
        $customerId    = request()->query('customer_id');
        $sectorId      = request()->query('sector_id');
        $seatId        = request()->query('seat_id');

        $modo = in_array($modoParam, ['nuevo', 'existente']) ? $modoParam : 'existente';
        $this->tipoProductoFiltro = in_array($tipoParam, ['abono', 'entrada', 'merch']) ? $tipoParam : null;
        $this->varianteProductoFiltro = in_array($varianteParam, ['renovacion', 'nuevo']) ? $varianteParam : null;

        // Si viene de la pagina VentaAbonos -> Renovacion, restringir metodos
        // de pago a los de taquilla (efectivo / TPV) y modo cliente fijo.
        if ($this->varianteProductoFiltro === 'renovacion' || $this->varianteProductoFiltro === 'nuevo') {
            $this->metodosPagoPermitidos = ['efectivo', 'tpv_fisico'];
        }

        // Si viene seat_id pero no sector_id, derivar el sector del asiento.
        if (is_numeric($seatId) && !is_numeric($sectorId)) {
            $sectorId = \App\Models\Seat::whereKey((int) $seatId)->value('sector_id');
        }

        // Flujo de abono: resolver producto + precio automáticamente (zona del
        // asiento / cliente). El operador no elige producto en estos flujos.
        $autoProd = $this->resolverAbonoAuto(
            is_numeric($seatId) ? (int) $seatId : null,
            is_numeric($customerId) ? (int) $customerId : null
        );
        if ($autoProd) {
            $nombreProd = is_array($autoProd->name)
                ? ($autoProd->name['es'] ?? '')
                : (string) $autoProd->getTranslation('name', 'es');
            $this->autoAbono = ['id' => $autoProd->id, 'name' => $nombreProd, 'price' => (float) $autoProd->price];
        }

        $this->form->fill([
            'modo_cliente'   => $modo,
            'customer_id'    => is_numeric($customerId) ? (int) $customerId : null,
            'sector_id'      => is_numeric($sectorId) ? (int) $sectorId : null,
            'seat_id'        => is_numeric($seatId) ? (int) $seatId : null,
            'product_id'     => $autoProd?->id,
            'unit_price'     => $autoProd ? (float) $autoProd->price : null,
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
                    ->description(fn () => match ($this->varianteProductoFiltro) {
                        'nuevo'      => 'Rellena los datos del nuevo abonado.',
                        'renovacion' => 'Abonado que renueva (ya seleccionado).',
                        default      => 'Busca por nombre, email o DNI. Si es nuevo, crea su ficha aquí mismo.',
                    })
                    ->schema([
                        // El selector de modo solo se muestra en el Cobro manual
                        // genérico. En los flujos de abono (nueva alta /
                        // renovación) el modo ya viene fijado por el flujo, así
                        // que se oculta para no confundir.
                        Radio::make('modo_cliente')
                            ->label('')
                            ->options([
                                'existente' => 'Cliente ya registrado',
                                'nuevo'     => 'Cliente nuevo (crear ahora)',
                            ])
                            ->inline()
                            ->live()
                            ->required()
                            ->visible(fn () => !$this->varianteProductoFiltro),

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

                Section::make('2. Abono y pago')
                    ->description(fn () => $this->varianteProductoFiltro ? 'Asiento, abono y método de pago.' : 'Producto y método de pago.')
                    ->columns(2)
                    ->schema([
                        // ── IZQUIERDA: abono / producto + asiento ──
                        FormGroup::make()
                            ->columnSpan(1)
                            ->schema([
                        // Resumen del abono resuelto automáticamente (flujo alta/renovación).
                        Placeholder::make('abono_resumen')
                            ->label('Abono')
                            ->visible(fn () => (bool) $this->varianteProductoFiltro)
                            ->content(fn () => $this->autoAbono
                                ? new \Illuminate\Support\HtmlString(
                                    '<strong>' . e($this->autoAbono['name']) . '</strong> — '
                                    . number_format($this->autoAbono['price'], 2, ',', '.') . ' €')
                                : 'No se ha podido determinar el abono para esta zona.'),

                        // El selector de producto manual solo en cobro genérico.
                        Select::make('product_id')
                            ->label('Producto')
                            ->visible(fn () => !$this->varianteProductoFiltro)
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
                            ->visible(fn () => !$this->varianteProductoFiltro)
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

                                // El botón grande de "Elegir butaca" solo se
                                // muestra si AÚN no hay butaca elegida (cobro
                                // manual genérico). Si vienes de Nuevas altas
                                // con la butaca ya seleccionada, se oculta — hay
                                // un pequeño "cambiar" en "Asiento elegido".
                                Placeholder::make('btn_elegir_butaca')
                                    ->hiddenLabel()
                                    ->visible(fn ($get) => empty($get('seat_id')))
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
                                        $txt = "🪑 " . ($seat->sector?->name ?? 'Sector ?') . " · Fila {$seat->row} · Butaca {$seat->number}";
                                        // Enlace discreto para cambiar la butaca si hiciera falta.
                                        $cambiar = '<a href="#" onclick="event.preventDefault();window.dispatchEvent(new CustomEvent(\'cm-open-estadio-modal\'))"'
                                            . ' style="margin-left:12px;font-size:13px;color:#2196F3;text-decoration:underline;cursor:pointer;">cambiar butaca</a>';
                                        return new \Illuminate\Support\HtmlString(
                                            '<span style="font-weight:600;">' . e($txt) . '</span>' . $cambiar
                                        );
                                    })
                                    ->extraAttributes(['id' => 'cm-asiento-display']),
                            ]),
                        ]),

                        // ── DERECHA: método de pago + botón comprar ──
                        FormGroup::make()
                            ->columnSpan(1)
                            ->schema([
                                Radio::make('metodo_pago')
                                    ->label('Método de pago')
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
                                    ->placeholder('Quien atiende, referencia, etc.')
                                    ->rows(2),

                                FormActions::make([
                                    FormAction::make('continuar')
                                        ->label(fn () => $this->varianteProductoFiltro ? 'Continuar compra' : 'Registrar cobro')
                                        ->icon(Heroicon::OutlinedCheckCircle)
                                        ->color('success')
                                        ->size('lg')
                                        ->requiresConfirmation()
                                        ->modalDescription('¿Confirmas registrar este cobro? Se creará el pedido pagado y se emitirá el ticket.')
                                        ->action(fn () => $this->procesarCobro()),
                                ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    /** Sin acciones en el header — el botón principal va al final del form. */
    protected function getHeaderActions(): array
    {
        return [];
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

            // 1b. En flujos de abono (alta/renovación) el producto y precio se
            //     resuelven SIEMPRE por la zona del asiento — no por el form.
            if ($this->tipoProductoFiltro === 'abono' && $this->varianteProductoFiltro) {
                $autoP = $this->resolverAbonoAuto(
                    isset($d['seat_id']) && is_numeric($d['seat_id']) ? (int) $d['seat_id'] : null,
                    isset($d['customer_id']) && is_numeric($d['customer_id']) ? (int) $d['customer_id'] : null
                );
                if ($autoP) {
                    $d['product_id'] = $autoP->id;
                    $d['unit_price'] = (float) $autoP->price;
                    $d['qty']        = 1;
                }
            }

            if (empty($d['product_id'])) {
                throw new \RuntimeException('No se pudo determinar el producto/abono. Revisa la zona del asiento.');
            }

            // 2. Crear Order + OrderItem dentro de transacción
            $product = Product::findOrFail($d['product_id']);
            $qty       = max(1, (int) ($d['qty'] ?? 1));
            $unitPrice = (float) ($d['unit_price'] ?? $product->price);
            $vatRate   = 21; // por defecto

            $subtotal = round($unitPrice * $qty, 2);
            $vat      = round($subtotal * ($vatRate / 100) / (1 + $vatRate / 100), 2);
            $total    = $subtotal;

            /** @var Order|null $orderCreada */
            $orderCreada = null;

            DB::transaction(function () use ($customer, $product, $d, $qty, $unitPrice, $vatRate, $subtotal, $vat, $total, &$orderCreada) {
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

                $orderCreada = $order;

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

                    // 3b. Heredar el QR antiguo del club. Si este customer ya tenía
                    //     un legacy_qr en algún ticket previo (carnet PVC impreso /
                    //     app antigua), lo copiamos a los tickets recién creados que
                    //     no lo tengan, para que su QR antiguo siga funcionando.
                    //     Para ALTAS NUEVAS (cliente sin legacy_qr) NO inventamos
                    //     nada: el ticket usará su QR propio (uuid.qr_token).
                    $legacyQr = Ticket::where('customer_id', $customer->id)
                        ->whereNotNull('legacy_qr')
                        ->value('legacy_qr');
                    if ($legacyQr) {
                        Ticket::whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))
                            ->whereNull('legacy_qr')
                            ->update(['legacy_qr' => $legacyQr]);
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

            // 5. Email de confirmación al cliente (con KILL SWITCH respetado).
            //    Genera/asegura las credenciales de la app del cliente y le
            //    envía email con su acceso + abono. Con el kill switch ON
            //    (estado actual) el método solo escribe en el log y NO conecta
            //    a SMTP — no sale ningún correo. Best-effort: no rompe el cobro.
            if ($orderCreada) {
                try {
                    $checkout = app(CheckoutService::class);
                    $fresh    = $orderCreada->fresh(['items.product', 'tickets', 'customer']);

                    // Password en claro: si era cliente NUEVO, la que se generó
                    // al crear su User. Si era EXISTENTE, asegurar credenciales
                    // (crea/resetea User si no tenía cuenta usable).
                    $appPassword = $this->appPasswordGenerada;
                    if ($appPassword === null && method_exists($checkout, 'ensureAppCredentials')) {
                        $appPassword = $checkout->ensureAppCredentials($customer->fresh());
                    }

                    $checkout->sendOrderConfirmationEmail($fresh, $appPassword);
                } catch (\Throwable $e) {
                    \Log::warning('Cobro manual: email confirmacion no enviado', [
                        'order' => $orderCreada->id,
                        'err'   => $e->getMessage(),
                    ]);
                }
            }

            $notif = Notification::make()
                ->title('Cobro registrado correctamente')
                ->body('Pedido creado y marcado como pagado. Email del cliente: ' . $customer->email)
                ->success();

            // Si se emitió un ticket de abono/entrada, ofrecer imprimir el
            // carnet PVC del primer ticket del pedido recién creado.
            if ($orderCreada && in_array($product->type, ['abono', 'entrada'], true)) {
                $ticketImprimir = Ticket::whereHas('orderItem', fn ($q) => $q->where('order_id', $orderCreada->id))
                    ->orderBy('id')
                    ->first();
                if ($ticketImprimir) {
                    $notif->actions([
                        \Filament\Notifications\Actions\Action::make('imprimir_carnet')
                            ->label('🖨️ Imprimir carnet')
                            ->url(route('admin.carnet', ['ticket' => $ticketImprimir->id]), shouldOpenInNewTab: true)
                            ->button(),
                    ]);
                }
            }

            $notif->send();

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

    /**
     * Contraseña EN CLARO generada para el cliente nuevo en este cobro.
     * Solo vive en memoria durante la request, para pasarla al email de
     * confirmación. Nunca se persiste en claro ni se loguea.
     */
    protected ?string $appPasswordGenerada = null;

    /** Crea User + Customer cuando el operador marca "cliente nuevo". */
    protected function crearCustomerYUser(array $d): Customer
    {
        return DB::transaction(function () use ($d) {
            $email = $d['email'];

            // Contraseña legible (8-10 chars, sin símbolos) para que el cliente
            // pueda escribirla a mano en la app desde el email. Se guarda
            // HASHEADA; el plano solo se retiene en memoria para el email.
            $plain = Str::password(10, letters: true, numbers: true, symbols: false, spaces: false);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')),
                    'password' => Hash::make($plain),
                ]
            );

            // Solo exponemos el plano si el User se ha CREADO ahora (wasRecentlyCreated).
            // Si ya existía (cliente recurrente con cuenta), no tocamos su clave.
            $this->appPasswordGenerada = $user->wasRecentlyCreated ? $plain : null;

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

            // Asegurar vínculo customer->user si el customer ya existía sin él.
            if (!$customer->user_id) {
                $customer->forceFill(['user_id' => $user->id])->save();
            }

            return $customer;
        });
    }
}
