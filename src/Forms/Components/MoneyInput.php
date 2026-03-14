<?php

namespace Wdog\FilamentUnusual\Forms\Components;

use Closure;
use NumberFormatter;
use Filament\Support\RawJs;
use Filament\Forms\Components\TextInput;

/**
 * A form field that displays and edits monetary values using ICU NumberFormatter.
 *
 * Requires the PHP `intl` extension with full ICU data (icu-data-full).
 *
 * Usage:
 *   MoneyInput::make('price')
 *   MoneyInput::make('price')->currency('USD')->locale('en_US')->decimals(2)
 */
class MoneyInput extends TextInput
{
    protected string|Closure $currency = 'EUR';

    protected string|Closure|null $locale = null;

    protected int|Closure $decimals = 2;

    protected function setUp(): void
    {
        parent::setUp();

        // Format the stored value for display using ICU currency formatting.
        $this->formatStateUsing(function (mixed $state): mixed {
            if (blank($state)) {
                return null;
            }

            $formatter = $this->makeCurrencyFormatter();

            return $formatter->formatCurrency((float) $state, $this->getCurrency());
        });

        // Parse the formatted string back to a float for storage.
        $this->dehydrateStateUsing(function (mixed $state): ?float {
            if (blank($state)) {
                return null;
            }

            $dec   = $this->getDecimalSeparator();
            $grp   = $this->getGroupingSeparator();
            $value = str_replace($grp, '', (string) $state);
            $value = str_replace($dec, '.', $value);

            return is_numeric($value) ? (float) $value : null;
        });

        // Currency symbol as prefix.
        $this->prefix(fn (): string => $this->getCurrencySymbol());

        // Alpine $money mask: formats the number as the user types.
        $this->mask(fn (): RawJs => RawJs::make(
            '$money($input, \'' . $this->getDecimalSeparator() . '\', \'\', ' . $this->getDecimals() . ')'
        ));
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    public function currency(string|Closure $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function decimals(int|Closure $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Resolved getters
    // -------------------------------------------------------------------------

    public function getCurrency(): string
    {
        return (string) $this->evaluate($this->currency);
    }

    public function getLocale(): string
    {
        return $this->evaluate($this->locale) ?? app()->getLocale();
    }

    public function getDecimals(): int
    {
        return (int) $this->evaluate($this->decimals);
    }

    public function getDecimalSeparator(): string
    {
        return $this->makeDecimalFormatter()->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    public function getGroupingSeparator(): string
    {
        return $this->makeDecimalFormatter()->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    public function getCurrencySymbol(): string
    {
        $symbol = $this->makeCurrencyFormatter()->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        return blank($symbol) ? $this->getCurrency() : $symbol;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function makeDecimalFormatter(): NumberFormatter
    {
        return new NumberFormatter($this->getLocale(), NumberFormatter::DECIMAL);
    }

    private function makeCurrencyFormatter(): NumberFormatter
    {
        $formatter = new NumberFormatter($this->getLocale(), NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $this->getDecimals());

        return $formatter;
    }
}
