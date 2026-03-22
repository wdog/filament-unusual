<?php

namespace Wdog\FilamentUnusual;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Wdog\FilamentUnusual\Panel\CalculatorFeature;
use Wdog\FilamentUnusual\Panel\StatsFeature;

class FilamentUnusualPlugin implements Plugin
{
    protected ?StatsFeature $statsFeature = null;

    protected ?CalculatorFeature $calculatorFeature = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-unusual';
    }

    public function showStats(bool $condition = true): static
    {
        if ($condition) {
            $this->statsFeature ??= new StatsFeature;
        } else {
            $this->statsFeature = null;
        }

        return $this;
    }

    public function showCalculator(bool $condition = true): static
    {
        if ($condition) {
            $this->calculatorFeature ??= new CalculatorFeature;
        } else {
            $this->calculatorFeature = null;
        }

        return $this;
    }

    /**
     * Disable one or more default stat rows.
     *
     * Available keys: 'loadTime', 'ram', 'laravel', 'filament', 'livewire'
     *
     * @param  array<string>|string  $keys
     */
    public function withoutStats(array|string $keys): static
    {
        $this->statsFeature?->withoutStats($keys);

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
        $this->statsFeature?->addStat($label, $value, $icon, $iconClass);

        return $this;
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        $this->statsFeature?->boot($panel);
        $this->calculatorFeature?->boot($panel);
    }
}
