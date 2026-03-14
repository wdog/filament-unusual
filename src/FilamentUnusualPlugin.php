<?php

namespace Wdog\FilamentUnusual;

use Filament\Panel;
use Filament\Contracts\Plugin;

class FilamentUnusualPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-unusual';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
