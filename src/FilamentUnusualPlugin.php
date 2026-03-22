<?php

namespace Wdog\FilamentUnusual;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Wdog\FilamentUnusual\Panel\CalculatorFeature;
use Wdog\FilamentUnusual\Panel\StatsFeature;

/**
 * FilamentUnusualPlugin — thin orchestrator.
 *
 * Registers optional panel features (topbar widgets) by delegating
 * configuration and render-hook registration to dedicated feature classes.
 * Each feature is only instantiated when its option is enabled.
 */
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

    /**
     * Enable the stats dropdown in the panel topbar.
     *
     * Pass a closure to configure the feature:
     *
     * ```php
     * ->showStats(fn (StatsFeature $stats) => $stats
     *     ->withoutDefaultStats()
     *     ->addStat('PHP', PHP_VERSION)
     * )
     * ```
     *
     * @param  bool|\Closure(StatsFeature): void  $condition
     */
    public function showStats(bool|\Closure $condition = true): static
    {
        $enabled = is_bool($condition) ? $condition : true;

        if ($enabled) {
            $this->statsFeature ??= new StatsFeature;

            if ($condition instanceof \Closure) {
                $condition($this->statsFeature);
            }
        } else {
            $this->statsFeature = null;
        }

        return $this;
    }

    /**
     * Enable the calculator slide-over in the panel topbar.
     */
    public function showCalculator(bool $condition = true): static
    {
        if ($condition) {
            $this->calculatorFeature ??= new CalculatorFeature;
        } else {
            $this->calculatorFeature = null;
        }

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
