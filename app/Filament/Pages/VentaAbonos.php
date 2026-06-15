<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seat;
use App\Models\Sector;
use App\Models\Season;
use App\Models\Ticket;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Pagina unificada de VENTA DE ABONOS.
 *
 * Replica el sistema del proveedor anterior del cliente:
 *   1. Renovacion de abonos (filtros por Nº Abonado / DNI / Apellidos)
 *   2. Nuevas altas (selector de sector + plano interactivo)
 *
 * Reemplaza a las paginas RenovacionAbono y AbonoNuevo del menu lateral.
 * La logica final de cobro (crear Order + Ticket QR + bloquear seat) se
 * delega en CobroManual via redireccion con query params pre-cargados.
 *
 * 6 acciones top (botones negros del header):
 *   LIBERAR ASIENTOS NO RENOVADOS · CONFIGURAR ABONOS · VENTAS
 *   GESTOR BUTACAS · OPCIONES DE BLOQUEOS · CAMPOS PERSONALIZADOS
 */
class VentaAbonos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Datos económicos';

    protected static ?int $navigationSort = 215;

    protected static ?string $navigationLabel = 'Venta de abonos';

    protected static ?string $title = 'Venta de abonos';

    protected string $view = 'filament.pages.venta-abonos';

    /** Filtro de busqueda de renovacion. */
    public string $filtroRenovacion = 'numero_abono';

    public string $busquedaRenovacion = '';

    /** Resultados de la busqueda de renovacion (lista de abonados encontrados). */
    public array $renovacionResultados = [];

    /** Filtro de la tabla de sectores en "Nuevas altas". */
    public string $busquedaSector = '';

    public ?int $sectorSeleccionado = null;

    /** Id del ticket recién renovado (para ofrecer "Imprimir carnet"). */
    protected ?int $ticketRenovadoId = null;

    /** Forms para los modales (configurar abonos, etc). */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([]);
    }

    /* =====================================================================
     * RENOVACION DE ABONOS — busqueda
     * ===================================================================== */

    /**
     * Busca abonados segun el filtro elegido (Nº Abonado, DNI, Apellidos).
     *
     * - numero_abono: coincidencia exacta socio_number o legacy_socio_id.
     * - dni: coincidencia parcial del DNI.
     * - apellidos: busqueda TOKENIZADA por nombre completo. Cada palabra que
     *   escriba el operador debe aparecer en first_name O last_name. Asi
     *   "antonio prueba" encuentra a un socio cuyo nombre es Antonio y
     *   apellidos "Prueba Tribuna", aunque el texto cruce ambos campos.
     *
     * Devuelve una LISTA (puede haber varios con el mismo apellido). El blade
     * muestra cada uno con su asiento del año pasado y un boton para renovar.
     */
    public function buscarAbonadoRenovacion(): void
    {
        $q = trim($this->busquedaRenovacion);
        if ($q === '') {
            Notification::make()->warning()->title('Introduce un valor de búsqueda')->send();
            return;
        }

        $query = Customer::query();

        switch ($this->filtroRenovacion) {
            case 'numero_abono':
                $query->where(fn ($w) => $w
                    ->where('socio_number', (int) $q)
                    ->orWhere('legacy_socio_id', (int) $q));
                break;

            case 'dni':
                $query->where('dni', 'like', "%{$q}%");
                break;

            case 'apellidos':
            default:
                // Tokenizar: cada palabra debe estar en nombre O apellidos.
                $tokens = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($tokens as $tok) {
                    $query->where(fn ($w) => $w
                        ->where('first_name', 'like', "%{$tok}%")
                        ->orWhere('last_name', 'like', "%{$tok}%"));
                }
                break;
        }

        $resultados = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(25)
            ->get();

        if ($resultados->isEmpty()) {
            $this->renovacionResultados = [];
            Notification::make()
                ->warning()
                ->title('No encontrado')
                ->body('No hay ningún abonado con ese criterio. Comprueba el campo seleccionado.')
                ->send();
            return;
        }

        $temporadaId = Season::current()?->id;

        $this->renovacionResultados = $resultados->map(function ($c) use ($temporadaId) {
            $ticket = Ticket::with('seat.sector')
                ->where('customer_id', $c->id)
                ->whereNotNull('seat_id')
                ->orderByDesc('id')
                ->first();

            $renovado = $temporadaId
                ? Ticket::where('customer_id', $c->id)
                    ->where('season_id', $temporadaId)
                    ->whereNotNull('seat_id')
                    ->exists()
                : false;

            return [
                'id'           => $c->id,
                'nombre'       => trim($c->first_name . ' ' . $c->last_name),
                'email'        => $c->email,
                'socio_number' => $c->socio_number ?? $c->legacy_socio_id,
                'dni'          => $c->dni,
                'phone'        => $c->phone,
                'asiento'      => $ticket && $ticket->seat
                    ? ($ticket->seat->sector?->name ?? 'Sector ?') . ' · Fila ' . $ticket->seat->row . ' · Asiento ' . $ticket->seat->number
                    : null,
                'renovado'     => $renovado,
            ];
        })->toArray();

        Notification::make()
            ->success()
            ->title($resultados->count() . ' abonado(s) localizado(s)')
            ->send();
    }

    /**
     * RENOVACIÓN RÁPIDA — renueva el MISMO asiento del abonado para la
     * temporada actual sin volver a elegir butaca ni salir de la pantalla.
     *
     * Crea Order pagado + OrderItem + Ticket nuevo (temporada actual, mismo
     * seat) y marca el seat como vendido (renovado). NO envia email (no llama
     * a CheckoutService, el ticket se crea directo).
     */
    public function renovacionRapida(int $customerId, string $metodo = 'efectivo'): void
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $temporada = Season::current();

            if (!$temporada) {
                Notification::make()->danger()->title('No hay temporada actual definida')->send();
                return;
            }

            // 1. Localizar el asiento que ya tiene (ultimo ticket con seat).
            $ticketAnterior = Ticket::with('seat.sector')
                ->where('customer_id', $customerId)
                ->whereNotNull('seat_id')
                ->orderByDesc('id')
                ->first();

            if (!$ticketAnterior || !$ticketAnterior->seat) {
                Notification::make()->danger()
                    ->title('Sin asiento previo')
                    ->body('Este abonado no tiene asiento de la temporada anterior. Usa "Cambiar asiento" para asignarle uno.')
                    ->send();
                return;
            }

            $seat   = $ticketAnterior->seat;
            $zoneId = $ticketAnterior->zone_id;

            // 2. Comprobar que no esta ya renovado esta temporada.
            $yaRenovado = Ticket::where('customer_id', $customerId)
                ->where('season_id', $temporada->id)
                ->where('seat_id', $seat->id)
                ->exists();
            if ($yaRenovado) {
                Notification::make()->warning()
                    ->title('Ya renovado')
                    ->body(trim($customer->first_name . ' ' . $customer->last_name) . ' ya tiene su abono renovado para esta temporada.')
                    ->send();
                return;
            }

            // 3. Producto de renovacion para la zona del asiento.
            $producto = Product::where('type', 'abono')
                ->where('season_id', $temporada->id)
                ->where('zone_id', $zoneId)
                ->where('name', 'like', '%enov%')
                ->first()
                ?? Product::where('type', 'abono')
                    ->where('season_id', $temporada->id)
                    ->where('zone_id', $zoneId)
                    ->first();

            if (!$producto) {
                Notification::make()->danger()
                    ->title('Sin producto de renovación')
                    ->body('No hay abono configurado para la zona de este asiento en la temporada actual.')
                    ->send();
                return;
            }

            // 3b. Heredar el QR antiguo del club si este abonado ya tenía uno.
            //     El carnet PVC impreso y la app antigua codifican ese token; al
            //     renovar, el ticket nuevo debe seguir respondiendo al mismo QR
            //     para que el abonado no tenga que cambiar de carnet.
            $legacyQr = Ticket::where('customer_id', $customer->id)
                ->whereNotNull('legacy_qr')
                ->value('legacy_qr');

            // 4. Crear Order + OrderItem + Ticket reusando el mismo seat.
            $precio = (float) $producto->price;
            DB::transaction(function () use ($customer, $producto, $seat, $temporada, $precio, $metodo, $legacyQr) {
                $ref = 'ACF-RENOV-' . now()->format('Ym') . '-' . str_pad((string) (Order::max('id') + 1), 6, '0', STR_PAD_LEFT);
                $vat = round($precio * 0.21 / 1.21, 2);

                $order = Order::create([
                    'reference'         => $ref,
                    'customer_id'       => $customer->id,
                    'status'            => 'paid',
                    'channel'           => 'admin',
                    'subtotal'          => $precio,
                    'vat'               => $vat,
                    'shipping_cost'     => 0,
                    'gestion_fee'       => 0,
                    'total'             => $precio,
                    'currency'          => 'EUR',
                    'payment_gateway'   => $metodo,
                    'payment_intent_id' => 'renov-' . \Illuminate\Support\Str::uuid()->toString(),
                    'paid_at'           => now(),
                    'admin_notes'       => 'Renovación rápida (mismo asiento)',
                ]);

                $nombreProd = is_array($producto->name)
                    ? ($producto->name['es'] ?? '')
                    : (string) $producto->getTranslation('name', 'es');

                $item = OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $producto->id,
                    'product_type' => 'abono',
                    'name'         => $nombreProd,
                    'sku'          => $producto->sku,
                    'qty'          => 1,
                    'unit_price'   => $precio,
                    'vat_rate'     => 21,
                    'subtotal'     => $precio,
                    'vat_amount'   => $vat,
                    'total'        => $precio,
                ]);

                $ticketNuevo = Ticket::create([
                    'order_item_id' => $item->id,
                    'customer_id'   => $customer->id,
                    'product_id'    => $producto->id,
                    'season_id'     => $temporada->id,
                    'zone_id'       => $producto->zone_id,
                    'seat_id'       => $seat->id,
                    'status'        => 'issued',
                    'holder_name'   => trim($customer->first_name . ' ' . $customer->last_name),
                    'holder_dni'    => $customer->dni,
                    'price_paid'    => $precio,
                    // Hereda el QR antiguo del club (null si era alta nueva).
                    'legacy_qr'     => $legacyQr,
                ]);

                $this->ticketRenovadoId = $ticketNuevo->id;

                $seat->update(['status' => 'sold']); // renovado = negro en el plano interno
            });

            $notif = Notification::make()->success()
                ->title('✅ Abono renovado')
                ->body(trim($customer->first_name . ' ' . $customer->last_name)
                    . ' — ' . ($seat->sector?->name ?? 'Sector') . ' Fila ' . $seat->row . ' Butaca ' . $seat->number
                    . ' · ' . number_format($precio, 2, ',', '.') . ' € (' . $metodo . ')');

            // Botón para imprimir el carnet PVC del ticket recién renovado.
            if ($this->ticketRenovadoId) {
                $notif->actions([
                    \Filament\Notifications\Actions\Action::make('imprimir_carnet')
                        ->label('🖨️ Imprimir carnet')
                        ->url(route('admin.carnet', ['ticket' => $this->ticketRenovadoId]), shouldOpenInNewTab: true)
                        ->button(),
                ]);
            }

            $notif->send();

            // Refrescar la lista para que el abonado pase a estado "renovado".
            $this->buscarAbonadoRenovacion();
        } catch (\Throwable $e) {
            Notification::make()->danger()
                ->title('Error en la renovación')
                ->body($e->getMessage())
                ->send();
            \Log::error('Renovacion rapida ERROR', ['customer' => $customerId, 'err' => $e->getMessage()]);
        }
    }

    /**
     * Texto descriptivo del asiento que tiene este abonado de la temporada
     * anterior (el que renovaria). Busca el ultimo ticket con seat asignado.
     */
    protected function asientoAbonadoTexto(Customer $c): ?string
    {
        $ticket = Ticket::with('seat.sector')
            ->where('customer_id', $c->id)
            ->whereNotNull('seat_id')
            ->orderByDesc('id')
            ->first();

        if (!$ticket || !$ticket->seat) {
            return null;
        }

        $seat = $ticket->seat;
        return ($seat->sector?->name ?? 'Sector ?') . ' · Fila ' . $seat->row . ' · Butaca ' . $seat->number;
    }

    /** Abre CobroManual con el cliente pre-seleccionado y filtro de renovacion. */
    public function irACobroRenovacion(int $customerId): mixed
    {
        return redirect("/admin/cobro-manual?modo=existente&tipo=abono&customer_id={$customerId}&variante=renovacion");
    }

    /* =====================================================================
     * NUEVAS ALTAS — seleccion de sector + plano
     * ===================================================================== */

    public function getSectoresProperty(): \Illuminate\Support\Collection
    {
        return Sector::query()
            ->where('available', true)
            ->withCount([
                'seats as seats_total',
                'seats as seats_free' => fn ($q) => $q->where('status', 'free'),
            ])
            ->when($this->busquedaSector, function ($q) {
                $term = "%{$this->busquedaSector}%";
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', $term)
                      ->orWhere('zone', 'like', $term);
                });
            })
            ->orderBy('zone')
            ->orderBy('parity')
            ->orderBy('number')
            ->get();
    }

    public function seleccionarSector(int $sectorId): void
    {
        $this->sectorSeleccionado = $sectorId;
    }

    /* =====================================================================
     * ACCIONES TOP (6 botones del header del cliente)
     * ===================================================================== */

    protected function getHeaderActions(): array
    {
        return [
            $this->actionLiberarAsientosNoRenovados(),
            $this->actionConfigurarAbonos(),
            $this->actionVentas(),
            $this->actionGestorButacas(),
            $this->actionOpcionesDeBloqueos(),
            $this->actionCamposPersonalizados(),
        ];
    }

    /** 1) LIBERAR ASIENTOS NO RENOVADOS — para abrir nuevas altas tras cierre renovacion. */
    protected function actionLiberarAsientosNoRenovados(): Action
    {
        return Action::make('liberar_no_renovados')
            ->label('Liberar asientos no renovados')
            ->icon(Heroicon::OutlinedLockOpen)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Liberar asientos de abonados que no han renovado')
            ->modalDescription(
                'Esta acción marca como LIBRES todas las butacas de abonos de la temporada anterior '
                . 'cuyos titulares NO han hecho una compra de abono en la temporada actual. '
                . 'Útil al cerrar el periodo de renovación para abrir el plano a nuevas altas. '
                . 'Operación reversible: el histórico de tickets queda intacto, solo cambia el estado del seat.'
            )
            ->modalSubmitActionLabel('Liberar ahora')
            ->action(function () {
                $temporadaActual = Season::current();
                if (!$temporadaActual) {
                    Notification::make()->danger()->title('No hay temporada actual definida')->send();
                    return;
                }

                $temporadaAnterior = Season::where('id', '<', $temporadaActual->id)
                    ->orderByDesc('id')
                    ->first();

                if (!$temporadaAnterior) {
                    Notification::make()->warning()->title('No hay temporada anterior, nada que liberar')->send();
                    return;
                }

                $customersConAbonoActual = Order::where('status', 'paid')
                    ->whereHas('items', fn ($q) => $q
                        ->whereIn('product_id', Product::where('type', 'abono')
                            ->where('season_id', $temporadaActual->id)
                            ->pluck('id'))
                    )
                    ->pluck('customer_id')
                    ->unique()
                    ->toArray();

                $ticketsSinRenovar = Ticket::whereNotNull('seat_id')
                    ->where('season_id', $temporadaAnterior->id)
                    ->whereNotIn('customer_id', $customersConAbonoActual)
                    ->get();

                $seatIds = $ticketsSinRenovar->pluck('seat_id')->unique()->filter()->values();
                $liberados = Seat::whereIn('id', $seatIds)
                    ->where('status', 'sold')
                    ->update(['status' => 'free']);

                Notification::make()
                    ->success()
                    ->title($liberados . ' butacas liberadas')
                    ->body('Temporada anterior: ' . $temporadaAnterior->name . '. Ya pueden venderse como nuevas altas.')
                    ->send();
            });
    }

    /** 2) CONFIGURAR ABONOS — fechas + precios por zona. Persiste en settings JSON. */
    protected function actionConfigurarAbonos(): Action
    {
        return Action::make('configurar_abonos')
            ->label('Configurar abonos')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->color('gray')
            ->modalHeading('Configuración de la campaña de abonos')
            ->modalWidth(Width::FourExtraLarge)
            ->fillForm(fn () => $this->cargarConfigAbonos())
            ->schema([
                Section::make('Periodo de renovación')->columns(2)->schema([
                    DatePicker::make('renovacion_inicio')->label('Inicio renovación')->required(),
                    DatePicker::make('renovacion_fin')->label('Fin renovación')->required(),
                ]),
                Section::make('Periodo de nuevas altas')->columns(2)->schema([
                    DatePicker::make('altas_inicio')->label('Inicio nuevas altas')->required(),
                    DatePicker::make('altas_fin')->label('Fin nuevas altas')->required(),
                ]),
                Section::make('Precios por zona / categoría')->schema([
                    Repeater::make('precios')
                        ->label('Precios')
                        ->columns(4)
                        ->schema([
                            TextInput::make('zona')->label('Zona')->required(),
                            TextInput::make('adulto')->label('Adulto (€)')->numeric()->step('0.01')->required(),
                            TextInput::make('joven')->label('Joven (€)')->numeric()->step('0.01'),
                            TextInput::make('jubilado')->label('Jubilado (€)')->numeric()->step('0.01'),
                        ])
                        ->defaultItems(0)
                        ->reorderable(true)
                        ->addActionLabel('Añadir zona'),
                ]),
            ])
            ->action(function (array $data) {
                Storage::disk('local')->put('abonos-config.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                Notification::make()
                    ->success()
                    ->title('Configuración guardada')
                    ->body('Periodos y precios de la campaña actualizados.')
                    ->send();
            });
    }

    protected function cargarConfigAbonos(): array
    {
        if (!Storage::disk('local')->exists('abonos-config.json')) {
            $precios = Sector::query()
                ->select('zone')
                ->selectRaw('AVG(price_adult) as price_adult_avg')
                ->selectRaw('AVG(price_youth) as price_youth_avg')
                ->where('available', true)
                ->groupBy('zone')
                ->get()
                ->map(fn ($s) => [
                    'zona'     => $s->zone,
                    'adulto'   => round((float) $s->price_adult_avg, 2),
                    'joven'    => round((float) $s->price_youth_avg, 2),
                    'jubilado' => round((float) $s->price_adult_avg * 0.7, 2),
                ])
                ->toArray();

            return [
                'renovacion_inicio' => now()->format('Y-m-d'),
                'renovacion_fin'    => now()->addMonth()->format('Y-m-d'),
                'altas_inicio'      => now()->addMonth()->addDay()->format('Y-m-d'),
                'altas_fin'         => now()->addMonths(2)->format('Y-m-d'),
                'precios'           => $precios,
            ];
        }
        return json_decode(Storage::disk('local')->get('abonos-config.json'), true) ?? [];
    }

    /** 3) VENTAS — reporting con stats + export CSV. */
    protected function actionVentas(): Action
    {
        return Action::make('ventas')
            ->label('Ventas')
            ->icon(Heroicon::OutlinedChartBar)
            ->color('gray')
            ->modalHeading('Resumen de ventas')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalContent(fn () => view('filament.pages.partials.venta-abonos-ventas', [
                'stats'   => $this->calcularStatsVentas(),
                'ultimas' => $this->ultimasVentas(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->extraModalFooterActions([
                Action::make('export_csv')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->action(fn () => $this->exportarVentasCsv()),
            ]);
    }

    protected function calcularStatsVentas(): array
    {
        $tempActual = Season::current();
        $abonoProductIds = Product::where('type', 'abono')
            ->when($tempActual, fn ($q) => $q->where('season_id', $tempActual->id))
            ->pluck('id');

        $base = Order::where('status', 'paid')
            ->whereHas('items', fn ($q) => $q->whereIn('product_id', $abonoProductIds));

        return [
            'total_pedidos'   => (clone $base)->count(),
            'total_ingresos'  => (clone $base)->sum('total'),
            'por_canal'       => (clone $base)->select('channel', DB::raw('COUNT(*) as c'), DB::raw('SUM(total) as t'))
                                    ->groupBy('channel')->get(),
            'por_dia_ultimos_30' => (clone $base)->where('created_at', '>=', now()->subDays(30))
                                    ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('COUNT(*) as c'), DB::raw('SUM(total) as t'))
                                    ->groupBy('fecha')->orderBy('fecha', 'desc')->get(),
        ];
    }

    protected function ultimasVentas(int $limit = 50): \Illuminate\Support\Collection
    {
        $abonoProductIds = Product::where('type', 'abono')->pluck('id');
        return Order::with('customer', 'items.product')
            ->where('status', 'paid')
            ->whereHas('items', fn ($q) => $q->whereIn('product_id', $abonoProductIds))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function exportarVentasCsv(): StreamedResponse
    {
        $abonoProductIds = Product::where('type', 'abono')->pluck('id');
        $ventas = Order::with('customer', 'items.product')
            ->where('status', 'paid')
            ->whereHas('items', fn ($q) => $q->whereIn('product_id', $abonoProductIds))
            ->orderByDesc('created_at')
            ->get();

        return response()->streamDownload(function () use ($ventas) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Referencia', 'Fecha', 'Cliente', 'Email', 'Canal', 'Pago', 'Total €', 'Producto']);
            foreach ($ventas as $o) {
                $producto = optional($o->items->first()?->product)->name ?? '—';
                fputcsv($out, [
                    $o->reference,
                    optional($o->paid_at)->format('Y-m-d H:i'),
                    optional($o->customer)->first_name . ' ' . optional($o->customer)->last_name,
                    optional($o->customer)->email,
                    $o->channel,
                    $o->payment_gateway,
                    number_format((float) $o->total, 2, ',', '.'),
                    $producto,
                ]);
            }
            fclose($out);
        }, 'ventas-abonos-' . now()->format('Y-m-d-His') . '.csv');
    }

    /** 4) GESTOR BUTACAS — tabla seats con filtros + acciones. */
    protected function actionGestorButacas(): Action
    {
        return Action::make('gestor_butacas')
            ->label('Gestor butacas')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('gray')
            ->modalHeading('Gestor de butacas')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalContent(fn () => view('filament.pages.partials.venta-abonos-butacas', [
                'sectores'   => Sector::orderBy('zone')->orderBy('number')->get(),
                'resumen'    => $this->resumenButacas(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    protected function resumenButacas(): array
    {
        return [
            'total'    => Seat::count(),
            'libres'   => Seat::where('status', 'free')->count(),
            'vendidas' => Seat::where('status', 'sold')->count(),
            'bloqueadas' => Seat::where('status', 'blocked')->count(),
            'reservadas' => Seat::where('status', 'reserved')->count(),
            'por_sector' => Seat::select('sector_id', 'status', DB::raw('COUNT(*) as c'))
                ->groupBy('sector_id', 'status')
                ->get()
                ->groupBy('sector_id'),
        ];
    }

    public function cambiarEstadoSeats(int $sectorId, string $nuevoEstado): void
    {
        if (!in_array($nuevoEstado, ['free', 'blocked', 'sold', 'reserved'])) {
            Notification::make()->danger()->title('Estado inválido')->send();
            return;
        }
        $count = Seat::where('sector_id', $sectorId)->update(['status' => $nuevoEstado]);
        Notification::make()
            ->success()
            ->title("Sector actualizado")
            ->body("{$count} butacas marcadas como {$nuevoEstado}.")
            ->send();
    }

    /** 5) OPCIONES DE BLOQUEOS — bloquear butacas por motivo (prensa, sponsor, etc). */
    protected function actionOpcionesDeBloqueos(): Action
    {
        return Action::make('opciones_bloqueos')
            ->label('Opciones de bloqueos')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('gray')
            ->modalHeading('Bloquear butacas por motivo institucional')
            ->modalWidth(Width::FourExtraLarge)
            ->fillForm(fn () => ['motivo' => 'prensa'])
            ->schema([
                Section::make('Crear bloqueo')->columns(2)->schema([
                    Select::make('sector_id')
                        ->label('Sector')
                        ->required()
                        ->options(Sector::orderBy('zone')->orderBy('number')->get()
                            ->mapWithKeys(fn ($s) => [$s->id => $s->name . ' (' . $s->zone . ')']))
                        ->searchable(),
                    Select::make('motivo')
                        ->label('Motivo del bloqueo')
                        ->required()
                        ->options([
                            'prensa'        => 'Prensa',
                            'sponsor'       => 'Patrocinador',
                            'autoridades'   => 'Autoridades / VIP',
                            'mantenimiento' => 'Mantenimiento',
                            'visitante'     => 'Afición visitante',
                            'otros'         => 'Otros',
                        ]),
                    TextInput::make('desde_fila')->label('Desde fila')->numeric()->required(),
                    TextInput::make('hasta_fila')->label('Hasta fila')->numeric()->required(),
                    Textarea::make('notas')->label('Notas')->columnSpanFull()->rows(2),
                ]),
            ])
            ->action(function (array $data) {
                $count = Seat::where('sector_id', $data['sector_id'])
                    ->whereBetween('row', [(int) $data['desde_fila'], (int) $data['hasta_fila']])
                    ->where('status', 'free')
                    ->update(['status' => 'blocked']);

                $bloqueos = json_decode(Storage::disk('local')->get('seat-blocks.json') ?? '[]', true);
                $bloqueos[] = [
                    'fecha'      => now()->toIso8601String(),
                    'sector_id'  => $data['sector_id'],
                    'desde_fila' => $data['desde_fila'],
                    'hasta_fila' => $data['hasta_fila'],
                    'motivo'     => $data['motivo'],
                    'notas'      => $data['notas'] ?? null,
                    'butacas'    => $count,
                ];
                Storage::disk('local')->put('seat-blocks.json', json_encode($bloqueos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                Notification::make()
                    ->success()
                    ->title("{$count} butacas bloqueadas")
                    ->body('Motivo: ' . $data['motivo'])
                    ->send();
            });
    }

    /** 6) CAMPOS PERSONALIZADOS — campos extra para el form de alta de abono. */
    protected function actionCamposPersonalizados(): Action
    {
        return Action::make('campos_personalizados')
            ->label('Campos personalizados')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->modalHeading('Campos extra del formulario de alta de abono')
            ->modalWidth(Width::FourExtraLarge)
            ->fillForm(fn () => [
                'campos' => json_decode(Storage::disk('local')->get('custom-fields.json') ?? '[]', true) ?: [],
            ])
            ->schema([
                Section::make('Campos del formulario público')
                    ->description('Estos campos se añaden al alta de abono nuevo además de los datos básicos.')
                    ->schema([
                        Repeater::make('campos')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                TextInput::make('clave')->label('Clave técnica')->required()
                                    ->helperText('Ej: talla_camiseta')->columnSpan(1),
                                TextInput::make('etiqueta')->label('Etiqueta visible')->required()->columnSpan(1),
                                Select::make('tipo')->label('Tipo')->required()->columnSpan(1)
                                    ->options([
                                        'text'     => 'Texto corto',
                                        'textarea' => 'Texto largo',
                                        'number'   => 'Número',
                                        'select'   => 'Selector',
                                        'checkbox' => 'Sí/No',
                                        'date'     => 'Fecha',
                                    ]),
                                Select::make('requerido')->label('Obligatorio')->columnSpan(1)
                                    ->options(['1' => 'Sí', '0' => 'No'])->default('0'),
                                Textarea::make('opciones')->label('Opciones (1 por línea, solo si tipo=Selector)')
                                    ->columnSpanFull()->rows(2),
                            ])
                            ->defaultItems(0)
                            ->reorderable(true)
                            ->addActionLabel('Añadir campo'),
                    ]),
            ])
            ->action(function (array $data) {
                Storage::disk('local')->put('custom-fields.json', json_encode($data['campos'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                Notification::make()
                    ->success()
                    ->title('Campos personalizados guardados')
                    ->body(count($data['campos'] ?? []) . ' campos definidos.')
                    ->send();
            });
    }
}
