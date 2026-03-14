<?php

namespace Wdog\FilamentUnusual;

use NumberFormatter;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\RawJs;
use Filament\Forms\Components\TextInput;

class FilamentUnusualServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-unusual');

        TextInput::macro('money', function (
            string $currency = 'EUR',
            ?string $locale = null,
            ?int $decimals = 2
        ) {
            /** @var TextInput $this */
            $locale ??= app()->getLocale();

            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

            $decimalSeparator  = $formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
            $thousandSeparator = $formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
            $symbol            = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
            $mask              = RawJs::make('$money($input, \'' . $decimalSeparator . '\', \'\', ' . $decimals . ')');

            return $this
                ->prefix($symbol)
                ->mask($mask)
                ->formatStateUsing(function ($state) use ($locale, $currency, $decimals) {
                    if ($state === null) {
                        return;
                    }

                    $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
                    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

                    return $formatter->formatCurrency((float) $state, $currency);
                })
                ->dehydrateStateUsing(function ($state) use ($decimalSeparator, $thousandSeparator) {
                    if ($state === null) {
                        return;
                    }

                    $value = str_replace($thousandSeparator, '', $state);
                    $value = str_replace($decimalSeparator, '.', $value);

                    return (float) $value;
                });
        });

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
