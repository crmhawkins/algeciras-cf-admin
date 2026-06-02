@php
    /**
     * Variables disponibles (vienen de MatchAttendanceStatsWidget::getViewData()):
     *   @var string                     $state    'live' | 'upcoming' | 'none'
     *   @var \App\Models\FootballMatch|null $match
     *   @var array|null                  $stats    total_scanned, total_capacity, percent, by_zone
     *   @var int|null                    $capacity
     */
    $pollingInterval = $this->getPollingInterval();
    $state = $state ?? 'none';

    $algecirasRed = '#CF2E2E';
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class(['fi-wi-match-attendance-stats'])
    "
>
    <x-filament::section>
        @if ($state === 'live')
            @php
                $kickoff = $match->kickoff_at?->format('d/m/Y H:i') ?? '—';
                $title = trim('J' . $match->matchday . ' · Algeciras CF vs ' . $match->opponent);
            @endphp

            <div class="space-y-6">
                {{-- Cabecera --}}
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-red-700 dark:bg-red-900/30 dark:text-red-300">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full" style="background-color: {{ $algecirasRed }};"></span>
                            </span>
                            En directo
                        </div>
                        <h2 class="mt-2 text-xl font-bold text-gray-950 dark:text-white">
                            Aforo en directo — {{ $title }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Saque {{ $kickoff }} · {{ $match->stadium ?: '—' }}
                        </p>
                    </div>
                </div>

                {{-- Número grande + barra global --}}
                <div>
                    <div class="flex items-baseline justify-between gap-4">
                        <div class="text-4xl font-extrabold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['total_scanned'], 0, ',', '.') }}
                            <span class="text-2xl font-medium text-gray-500 dark:text-gray-400">
                                / {{ number_format($stats['total_capacity'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="text-2xl font-bold" style="color: {{ $algecirasRed }};">
                            {{ number_format($stats['percent'], 1, ',', '.') }}%
                        </div>
                    </div>

                    <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-3 rounded-full transition-all duration-700 ease-out"
                            style="width: {{ $stats['percent'] }}%; background-color: {{ $algecirasRed }};"
                        ></div>
                    </div>
                </div>

                {{-- Listado por zona --}}
                @if (! empty($stats['by_zone']))
                    <div>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Por zona
                        </h3>
                        <div class="space-y-3">
                            @foreach ($stats['by_zone'] as $row)
                                @php
                                    $rowColor = $row['color'] ?: $algecirasRed;
                                @endphp
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block h-3 w-3 rounded-full" style="background-color: {{ $rowColor }};"></span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $row['name'] }}</span>
                                        </div>
                                        <div class="font-mono tabular-nums text-gray-700 dark:text-gray-200">
                                            {{ number_format($row['scanned'], 0, ',', '.') }}
                                            <span class="text-gray-400">/ {{ number_format($row['capacity'], 0, ',', '.') }}</span>
                                            <span class="ml-2 text-xs text-gray-500">({{ number_format($row['percent'], 1, ',', '.') }}%)</span>
                                        </div>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700/60">
                                        <div
                                            class="h-2 rounded-full transition-all duration-700 ease-out"
                                            style="width: {{ $row['percent'] }}%; background-color: {{ $rowColor }};"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Aún no hay accesos escaneados.
                    </p>
                @endif

                <p class="text-xs text-gray-400 dark:text-gray-500">
                    Actualización automática cada {{ $pollingInterval }}.
                </p>
            </div>

        @elseif ($state === 'upcoming')
            @php
                $kickoff = $match->kickoff_at?->isoFormat('dddd D [de] MMMM, HH:mm') ?? '—';
                $capacity = $capacity ?? 0;
            @endphp

            <div class="flex flex-col gap-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Próximo partido en casa
                </div>
                <div class="text-lg font-bold text-gray-950 dark:text-white">
                    {{ $match->matchday ? 'J' . $match->matchday . ' · ' : '' }}Algeciras CF vs {{ $match->opponent }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    {{ ucfirst($kickoff) }} · {{ $match->stadium ?: '—' }}
                </div>
                @if ($capacity > 0)
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Aforo total del estadio: <span class="font-semibold">{{ number_format($capacity, 0, ',', '.') }}</span> localidades.
                    </div>
                @endif
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    El aforo en directo aparecerá automáticamente desde 4 horas antes del saque.
                </p>
            </div>

        @else
            <div class="flex flex-col items-start gap-1">
                <div class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Aforo en directo
                </div>
                <div class="text-base text-gray-700 dark:text-gray-200">
                    Sin partidos próximos programados en casa.
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
