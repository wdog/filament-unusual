<?php

namespace Wdog\FilamentUnusual\Forms\Components;

use Closure;
use NumberFormatter;
use Filament\Support\RawJs;
use Filament\Forms\Components\TextInput;

/**
 * A form field that displays and edits monetary values using ICU NumberFormatter.
 *
 * All closures registered in setUp() are evaluated at render time, which means
 * fluent configuration methods (currency, locale, decimals) are already resolved
 * by the time the field is rendered.
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

    /** Cached decimal-style NumberFormatter instance for the current request. */
    private ?NumberFormatter $decimalFormatter = null;

    /** Cached currency-style NumberFormatter instance for the current request. */
    private ?NumberFormatter $currencyFormatter = null;

    /**
     * Bootstrap the component: register prefix, Alpine mask, and state transformers.
     *
     * All closures are intentionally lazy so fluent configuration applied after
     * make() (e.g. ->currency('USD')) is already in place when they run.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Currency symbol shown as a prefix — always visible, even on empty fields.
        $this->prefix(fn (): string => $this->getCurrencySymbol());

        // Alpine $money mask: formats the raw number as the user types.
        // The grouping separator is intentionally left empty so we only use the
        // decimal separator; grouping is handled visually by the mask itself.
        $this->mask(fn (): RawJs => RawJs::make(
            '$money($input, \'' . $this->getDecimalSeparator() . '\', \'\', ' . $this->getDecimals() . ')'
        ));

        // Hydrate: convert the stored float into a locale-aware display string.
        $this->formatStateUsing(function (mixed $state): ?string {
            if ($state === null) {
                return null;
            }

            return $this->makeCurrencyFormatter()->formatCurrency((float) $state, $this->getCurrency()) ?: null;
        });

        // Dehydrate: strip locale-specific formatting and convert back to a float.
        $this->dehydrateStateUsing(function (mixed $state): ?float {
            if ($state === null) {
                return null;
            }

            $value = str_replace($this->getGroupingSeparator(), '', (string) $state);
            $value = str_replace($this->getDecimalSeparator(), '.', $value);

            return is_numeric($value) ? (float) $value : null;
        });
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    /**
     * Set the ISO 4217 currency code (e.g. 'EUR', 'USD').
     * Accepts a static string or a Closure for dynamic resolution.
     */
    public function currency(string|Closure $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Override the ICU locale used for formatting (e.g. 'it_IT', 'en_US').
     * Defaults to the application locale when null.
     */
    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the number of decimal places to display (default: 2).
     */
    public function decimals(int|Closure $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Resolved getters
    // -------------------------------------------------------------------------

    /**
     * Return the resolved ISO 4217 currency code.
     */
    public function getCurrency(): string
    {
        return (string) $this->evaluate($this->currency);
    }

    /**
     * Return the resolved ICU locale string, falling back to the app locale.
     */
    public function getLocale(): string
    {
        return $this->evaluate($this->locale) ?? app()->getLocale();
    }

    /**
     * Return the resolved number of decimal places.
     */
    public function getDecimals(): int
    {
        return (int) $this->evaluate($this->decimals);
    }

    /**
     * Return the locale-aware decimal separator character (e.g. ',' for Italian).
     */
    public function getDecimalSeparator(): string
    {
        return $this->makeDecimalFormatter()->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    /**
     * Return the locale-aware thousands grouping separator character (e.g. '.' for Italian).
     */
    public function getGroupingSeparator(): string
    {
        return $this->makeDecimalFormatter()->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    /**
     * Return the currency symbol for the current locale and currency code.
     *
     * Uses the ICU locale extension `@currency=XXX` so that CURRENCY_SYMBOL
     * resolves to the actual glyph (e.g. '€') rather than the generic '¤'.
     * Falls back to the ISO code when ICU cannot resolve the symbol.
     */
    public function getCurrencySymbol(): string
    {
        $fmt    = new NumberFormatter($this->getLocale() . '@currency=' . $this->getCurrency(), NumberFormatter::CURRENCY);
        $symbol = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        return (blank($symbol) || $symbol === '¤') ? $this->getCurrency() : $symbol;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Return a cached decimal NumberFormatter for separator lookups.
     *
     * Caching avoids redundant ICU initialisation when both getDecimalSeparator()
     * and getGroupingSeparator() are called in the same render cycle.
     */
    private function makeDecimalFormatter(): NumberFormatter
    {
        return $this->decimalFormatter ??= new NumberFormatter($this->getLocale(), NumberFormatter::DECIMAL);
    }

    /**
     * Return a cached currency NumberFormatter configured with the correct fraction digits.
     *
     * Used for formatCurrency() calls during state hydration.
     */
    private function makeCurrencyFormatter(): NumberFormatter
    {
        if ($this->currencyFormatter === null) {
            $this->currencyFormatter = new NumberFormatter($this->getLocale(), NumberFormatter::CURRENCY);
            $this->currencyFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $this->getDecimals());
        }

        return $this->currencyFormatter;
    }
}
