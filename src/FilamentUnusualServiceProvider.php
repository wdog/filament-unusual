<?php

namespace Wdog\FilamentUnusual;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;

class FilamentUnusualServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-unusual');

        // !
        FilamentAsset::register(
            [
                Css::make('filament-unusual', __DIR__ . '/../dist/plugin.css'),
                AlpineComponent::make('columns/date-picker', __DIR__ . '/../dist/components/columns/date-picker.js'),
            ],
            'wdog/filament-unusual'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\PrunePermissions::class,
            ]);
        }
    }
}
