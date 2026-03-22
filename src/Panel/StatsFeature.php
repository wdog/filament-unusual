<?php

namespace Wdog\FilamentUnusual\Panel;

use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class StatsFeature
{
    /** @var array<string> */
    protected array $disabledStats = [];

    /** @var array<array{label: string, value: string|\Closure, icon: string|null, iconClass: string}> */
    protected array $extraStats = [];

    /**
     * Disable one or more default stat rows.
     *
     * Available keys: 'loadTime', 'ram', 'laravel', 'filament', 'livewire'
     *
     * @param  array<string>|string  $keys
     */
    public function withoutStats(array|string $keys): static
    {
        $this->disabledStats = array_merge($this->disabledStats, (array) $keys);

        return $this;
    }

    /**
     * Add a custom stat row to the dropdown.
     *
     * @param  string  $label  Row label.
     * @param  string|\Closure  $value  Resolved value or a closure returning it.
     * @param  string|null  $icon  Raw SVG <path> string. Defaults to a generic tag icon.
     * @param  string  $iconClass  Tailwind colour classes for the icon.
     */
    public function addStat(
        string $label,
        string|\Closure $value,
        ?string $icon = null,
        string $iconClass = 'text-gray-500 dark:text-gray-400',
    ): static {
        $this->extraStats[] = compact('label', 'value', 'icon', 'iconClass');

        return $this;
    }

    public function boot(Panel $panel): void
    {
        $disabledStats = $this->disabledStats;
        $extraStats = $this->extraStats;

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn () => view('filament-unusual::panel.stats-dropdown', [
                'disabledStats' => $disabledStats,
                'extraStats' => $extraStats,
            ]),
        );
    }
}
