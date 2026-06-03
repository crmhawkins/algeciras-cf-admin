<?php

namespace App\Filament\Pages;

use App\Mail\BienvenidaClienteMail;
use App\Models\Customer;
use App\Models\FootballMatch;
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
 * Página COBRAR ENTRADA — flujo rápido para taquilla.
 *
 * Flujo:
 *  1. Selecciona partido + tipo de entrada (Product type=entrada)
 *  2. Cliente: existente (search) o nuevo (form)
 *  3. Método de cobro
 *  4. Submit → Order(paid) + Ticket QR + email
 *
 * Si el cliente es nuevo: se crea User + Customer + email bienvenida con
 * credenciales. En cualquier caso recibe el email con el QR del partido.
 */
class CobrarEntrada extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Datos económicos';

    protected static ?int $navigationSort = 240;

    protected static ?string $navigationLabel = 'Cobrar entrada';

    protected static ?string $title = 'Cobrar entrada para un partido';

    protected string $view = 'filament.pages.cobrar-entrada';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'modo'        => 'existente',
            'metodo_pago' => 'efectivo',
            'qty'         => 1,
            'province'    => 'Cádiz',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('1. Partido y entrada')
                    ->columns(2)
                    ->schema([
                        Select::make('match_id')
                            ->label('Partido')
                            ->required()
                            ->searchable()
                            ->options(fn () => FootballMatch::query()
                                ->where('kickoff_at', '>=', now()->subDays(1))
                                ->orderBy('kickoff_at')
                                ->limit(30)
                                ->get()
                                ->mapWithKeys(fn ($m) => [
                                    $m->id => ($m->equipoLocal ?? 'Algeciras CF') . ' vs ' . ($m->equipoVisitante ?? '?')
                                            . ' — ' . optional($m->kickoff_at)->format('d/m H:i'),
                                ])
                                ->toArray()),

                        Select::make('product_id')
                            ->label('Tipo de entrada')
                            ->required()
                            ->searchable()
                            ->live()
                            ->options(fn () => Product::where('type', 'entrada')
                                ->where('active', 1)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => $p->name . ' — €' . number_format((float) $p->price, 2)])
                                ->toArray())
                            ->afterStateUpdated(function ($state, callable $set) {
                                $p = Product::find($state);
                                if ($p) $set('precio', $p->price);
                            }),

                        TextInput::make('qty')->label('Cantidad')->numeric()->minValue(1)->default(1)->required(),
                        TextInput::make('precio')->label('Precio unitario (€)')->numeric()->step('0.01')->required(),
                    ]),

                Section::make('2. Cliente')
                    ->schema([
                        Radio::make('modo')
                            ->label('')
                            ->options([
                                'existente' => 'Cliente ya registrado',
                                'nuevo'     => 'Cliente nuevo (crear cuenta y enviar credenciales por email)',
                            ])
                            ->inline()
                            ->live()
                            ->required(),

                        Select::make('customer_id')
                            ->label('Buscar cliente')
                            ->visible(fn (Get $get) => $get('modo') === 'existente')
                            ->required(fn (Get $get) => $get('modo') === 'existente')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $q) => Customer::query()
                                ->where(fn ($w) => $w
                                    ->where('first_name', 'like', "%$q%")
                                    ->orWhere('last_name',  'like', "%$q%")
                                    ->orWhere('email',      'like', "%$q%")
                                    ->orWhere('dni',        'like', "%$q%")
                                    ->orWhere('phone',      'like', "%$q%"))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => trim($c->first_name . ' ' . $c->last_name) . ' — ' . $c->email,
                                ])
                                ->toArray())
                            ->getOptionLabelUsing(fn ($v) => optional(Customer::find($v))->email),
                    ]),

                Section::make('Datos del cliente nuevo')
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

                Section::make('3. Método de cobro')
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
                ->label('Cobrar entrada y enviar email')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->procesar()),
        ];
    }

    protected function procesar(): void
    {
        try {
            $d = $this->form->getState();
            $product = Product::findOrFail($d['product_id']);
            $qty     = max(1, (int) ($d['qty'] ?? 1));
            $unit    = (float) ($d['precio'] ?? $product->price);
            $subtotal= round($unit * $qty, 2);
            $vat     = round($subtotal * 0.21 / 1.21, 2);

            // 1. Customer
            [$customer, $passwordPlain] = $this->resolverCustomer($d);

            // 2. Order + items
            $orderId = DB::transaction(function () use ($customer, $product, $d, $qty, $unit, $subtotal, $vat) {
                $reference = 'ACF-ENT-' . now()->format('Ym') . '-' . str_pad((string) (Order::max('id') + 1), 6, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'reference'        => $reference,
                    'customer_id'      => $customer->id,
                    'status'           => 'paid',
                    'channel'          => 'admin',
                    'subtotal'         => $subtotal,
                    'vat'              => $vat,
                    'shipping_cost'    => 0,
                    'gestion_fee'      => 0,
                    'total'            => $subtotal,
                    'currency'         => 'EUR',
                    'payment_gateway'  => $d['metodo_pago'] ?? 'efectivo',
                    'payment_intent_id'=> 'manual-' . Str::uuid()->toString(),
                    'paid_at'          => now(),
                    'admin_notes'      => $d['admin_notes'] ?? null,
                ]);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'product_type'=> 'entrada',
                    'name'       => $product->name,
                    'qty'        => $qty,
                    'unit_price' => $unit,
                    'vat_rate'   => 21,
                    'subtotal'   => $subtotal,
                    'vat_amount' => $vat,
                    'total'      => $subtotal,
                    'meta'       => ['match_id' => $d['match_id'] ?? null],
                ]);

                return $order->id;
            });

            try {
                $checkout = app(CheckoutService::class);
                $order = Order::with('items')->find($orderId);
                if ($order && method_exists($checkout, 'generateTicketsForOrder')) {
                    $checkout->generateTicketsForOrder($order);
                }
            } catch (\Throwable $e) {
                Log::warning('CobrarEntrada ticket gen warning', ['err' => $e->getMessage()]);
            }

            if ($passwordPlain) {
                try {
                    Mail::to($customer->email)->send(new BienvenidaClienteMail($customer, $passwordPlain, 'entrada'));
                } catch (\Throwable $e) {
                    Log::warning('CobrarEntrada welcome mail warning', ['err' => $e->getMessage()]);
                }
            }

            Notification::make()
                ->title('Entrada cobrada y email enviado')
                ->body('Email enviado a ' . $customer->email)
                ->success()
                ->duration(10000)
                ->send();

            $this->form->fill(['modo' => 'existente', 'metodo_pago' => 'efectivo', 'qty' => 1, 'province' => 'Cádiz']);
        } catch (\Throwable $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
            Log::error('CobrarEntrada ERROR', ['err' => $e->getMessage()]);
        }
    }

    /** @return array{0: Customer, 1: ?string} */
    protected function resolverCustomer(array $d): array
    {
        if (($d['modo'] ?? 'existente') === 'existente') {
            return [Customer::findOrFail($d['customer_id']), null];
        }

        $passwordPlain = 'Algeciras' . now()->year . '!' . strtoupper(Str::random(4));
        $customer = DB::transaction(function () use ($d, $passwordPlain) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                ['name' => trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')),
                 'password' => Hash::make($passwordPlain)]
            );
            return Customer::firstOrCreate(
                ['email' => $d['email']],
                [
                    'user_id' => $user->id,
                    'first_name' => $d['first_name'], 'last_name' => $d['last_name'],
                    'phone' => $d['phone'], 'dni' => $d['dni'],
                    'birth_date' => $d['birth_date'],
                    'address' => $d['address'], 'city' => $d['city'],
                    'postal_code' => $d['postal_code'],
                    'province' => $d['province'] ?? 'Cádiz', 'country' => 'ES',
                ]
            );
        });

        return [$customer, $passwordPlain];
    }
}
