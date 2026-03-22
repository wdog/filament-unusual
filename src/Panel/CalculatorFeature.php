<?php

namespace Wdog\FilamentUnusual\Panel;

use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class CalculatorFeature
{
    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn () => view('filament-unusual::panel.calculator-button'),
        );
    }
}
