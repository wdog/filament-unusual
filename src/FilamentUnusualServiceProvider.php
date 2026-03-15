<?php

namespace Wdog\FilamentUnusual;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\AlpineComponent;

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
