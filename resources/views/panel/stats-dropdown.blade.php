@php
    $show = fn (string $key) => ! in_array($key, $disabledStats);

    $showLoadTime = $show('loadTime');
    $showRam      = $show('ram');
    $showLaravel  = $show('laravel');
    $showFilament = $show('filament');
    $showLivewire = $show('livewire');

    $loadMs          = $showLoadTime ? round((microtime(true) - LARAVEL_START) * 1000, 1) : null;
    $ramMb           = $showRam      ? round(memory_get_peak_usage(true) / 1024 / 1024, 1) : null;
    $laravelVersion  = $showLaravel  ? app()->version() : null;
    $filamentVersion = $showFilament ? (\Composer\InstalledVersions::getPrettyVersion('filament/filament') ?? '—') : null;
    $livewireVersion = $showLivewire ? (\Composer\InstalledVersions::getPrettyVersion('livewire/livewire') ?? '—') : null;

    $hasPerf     = $showRam;
    $hasVersions = $showLaravel || $showFilament || $showLivewire;
    // Resolve closures at render time (after all page queries have run)
    $extraStats = array_map(function (array $stat): array {
        $stat['value'] = is_callable($stat['value']) ? call_user_func($stat['value']) : $stat['value'];
        return $stat;
    }, $extraStats);

    $hasExtras   = count($extraStats) > 0;
    $hasDropdown = $hasPerf || $hasVersions || $hasExtras;

    // Default SVG icon used for custom stats when none is provided
    $defaultExtraIcon = '<path fill-rule="evenodd" d="M5.5 3A2.5 2.5 0 0 0 3 5.5v2.879a2.5 2.5 0 0 0 .732 1.767l6.5 6.5a2.5 2.5 0 0 0 3.536 0l2.878-2.878a2.5 2.5 0 0 0 0-3.536l-6.5-6.5A2.5 2.5 0 0 0 8.38 3H5.5zM6 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/>';
@endphp

@if ($showLoadTime || $hasDropdown)
<div class="fi-stats-wrap" x-data="{ open: false }" @click.away="open = false">

    {{-- Trigger --}}
    <button type="button" class="fi-stats-trigger" @click="{{ $hasDropdown ? 'open = !open' : '' }}" :aria-expanded="{{ $hasDropdown ? 'open' : 'false' }}" @unless($hasDropdown) disabled @endunless>
        {{-- Clock icon --}}
        <svg class="fi-stats-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h3.25a.75.75 0 0 0 0-1.5H10.75V5z" clip-rule="evenodd"/>
        </svg>
        @if ($showLoadTime)
            <strong class="fi-stats-ms">{{ $loadMs }}<span class="fi-stats-unit">ms</span></strong>
        @endif
    </button>

    @if ($hasDropdown)
    {{-- Dropdown --}}
    <div class="fi-stats-dropdown" x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

        {{-- RAM --}}
        @if ($showRam)
        <div class="fi-stats-row">
            <svg class="fi-stats-row-icon text-blue-500 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M4 5a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5zM4 13a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2z"/>
            </svg>
            <span class="fi-stats-row-label">RAM</span>
            <span class="fi-stats-row-value">{{ $ramMb }} MB</span>
        </div>
        @endif

        {{-- Divider: perf → versions --}}
        @if ($hasPerf && $hasVersions)
        <div class="fi-stats-divider"></div>
        @endif

        {{-- Laravel --}}
        @if ($showLaravel)
        <div class="fi-stats-row">
            <svg class="fi-stats-row-icon text-red-500 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 52" fill="currentColor" aria-hidden="true">
                <path d="M49.626 11.564a.809.809 0 0 1 .028.209v10.972a.8.8 0 0 1-.402.694l-9.209 5.302V39.25c0 .286-.152.55-.4.694L20.42 51.01c-.044.025-.092.041-.14.058-.018.006-.035.017-.054.022a.805.805 0 0 1-.41 0c-.022-.006-.042-.018-.063-.026-.044-.016-.09-.031-.132-.054L.402 39.944A.801.801 0 0 1 0 39.25V6.334c0-.072.01-.142.028-.209.006-.023.02-.044.028-.067.015-.042.029-.085.051-.124.015-.026.037-.047.055-.071.023-.032.044-.065.071-.093.023-.023.053-.04.079-.06.029-.022.055-.047.088-.065h.001l9.61-5.533a.802.802 0 0 1 .8 0l9.61 5.533h.002c.032.018.059.043.088.065.026.02.055.037.078.06.028.028.048.061.072.093.017.024.04.045.054.071.023.04.036.082.052.124.008.023.022.044.028.067zm-1.574 10.701v-9.01l-3.861 2.223-5.348 3.08v9.01l9.209-5.303zM38.04 37.832v-9.012l-5.268 3.022-15.034 8.631v9.092l20.302-11.733zM1.602 7.719v31.531l20.302 11.733v-9.092l-10.64-6.1-.002-.001-.002-.002c-.033-.018-.06-.044-.09-.066-.025-.019-.054-.036-.076-.058l-.002-.003c-.026-.025-.044-.056-.066-.084-.02-.027-.044-.05-.06-.078l-.001-.003c-.018-.03-.029-.066-.042-.1-.013-.03-.03-.058-.038-.09v-.001c-.01-.038-.012-.078-.016-.117-.004-.03-.012-.06-.012-.09v-21.483L9.018 9.94 1.602 7.719zm8.81-5.994L1.602 7.72l8.81 5.994 9.209-5.302-9.21-5.988zm5.267 24.283l5.348-3.08V7.72l-3.861 2.222-5.348 3.08v15.208l3.861-2.222zM39.44 5.3L30.23 10.6l9.21 5.302 9.208-5.302L39.439 5.3zm-.801 11.988l-5.348-3.08-3.861-2.222v9.01l5.348 3.08 3.861 2.223V17.288zm-18.914 9.905l15.034-8.631 7.516-4.324-8.808-5.993-9.61 5.532-9.209 5.302 5.077 2.92z"/>
            </svg>
            <span class="fi-stats-row-label">Laravel</span>
            <span class="fi-stats-row-value">{{ $laravelVersion }}</span>
        </div>
        @endif

        {{-- Filament --}}
        @if ($showFilament)
        <div class="fi-stats-row">
            <svg class="fi-stats-row-icon text-amber-500 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.83-4.401z" clip-rule="evenodd"/>
            </svg>
            <span class="fi-stats-row-label">Filament</span>
            <span class="fi-stats-row-value">{{ $filamentVersion }}</span>
        </div>
        @endif

        {{-- Livewire --}}
        @if ($showLivewire)
        <div class="fi-stats-row">
            <svg class="fi-stats-row-icon text-pink-500 dark:text-pink-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M11.983 1.907a.75.75 0 0 0-1.292-.657l-8.5 9.5A.75.75 0 0 0 2.75 12h6.572l-1.305 6.093a.75.75 0 0 0 1.292.657l8.5-9.5A.75.75 0 0 0 17.25 8h-6.572l1.305-6.093z"/>
            </svg>
            <span class="fi-stats-row-label">Livewire</span>
            <span class="fi-stats-row-value">{{ $livewireVersion }}</span>
        </div>
        @endif

        {{-- Divider: defaults → extras --}}
        @if (($hasPerf || $hasVersions) && $hasExtras)
        <div class="fi-stats-divider"></div>
        @endif

        {{-- Custom stats --}}
        @foreach ($extraStats as $stat)
        <div class="fi-stats-row">
            <svg class="fi-stats-row-icon {{ $stat['iconClass'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                {!! $stat['icon'] ?? $defaultExtraIcon !!}
            </svg>
            <span class="fi-stats-row-label">{{ $stat['label'] }}</span>
            <span class="fi-stats-row-value">{{ $stat['value'] }}</span>
        </div>
        @endforeach

    </div>
    @endif

</div>
@endif
