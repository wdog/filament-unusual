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
