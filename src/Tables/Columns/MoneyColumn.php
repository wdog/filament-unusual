<?php

namespace Wdog\FilamentUnusual\Tables\Columns;

use Closure;
use NumberFormatter;
use Filament\Tables\Columns\TextColumn;

/**
 * A read-only table column that formats integer amounts stored in the database
 * (cents, millicents, or any other sub-unit) as a localised currency string.
 *
 * Usage:
 *   MoneyColumn::make('price')                      // €12.50  (cents, EUR, app locale)
 *   MoneyColumn::make('price')->currency('USD')      // $12.50
 *   MoneyColumn::make('price')->divisor(1000)        // millicents → units
 *   MoneyColumn::make('price')->locale('en_US')      // force locale
 *
 * The column is built on top of TextColumn so all standard Filament modifiers
 * (sortable, searchable, badge, summarize…) work without extra effort.
 */
class MoneyColumn extends TextColumn
{
    /**
     * ISO 4217 currency code (e.g. 'EUR', 'USD', 'GBP').
     * Passed directly to NumberFormatter::formatCurrency().
     */
    protected string|Closure $currency = 'EUR';

    protected int|Closure $decimals = 2;

    /**
     * ICU locale string used for number/symbol formatting.
     * Defaults to the application locale when null.
     */
    protected string|Closure|null $locale = null;

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        // Apply the formatting at render time so the raw integer is preserved
        // in the column state (useful for sorting, summarizing, exporting).
        $this->formatStateUsing(function (mixed $state): string {
            if (blank($state)) {
                return '—';
            }

            $currency = (string) $this->evaluate($this->currency);
            $decimals = (int) $this->evaluate($this->decimals);
            $locale   = $this->evaluate($this->locale) ?? app()->getLocale();

            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

            $formatted = $formatter->formatCurrency($state, $currency);

            // NumberFormatter returns false on failure (e.g. unknown currency).
            return $formatted !== false ? $formatted : number_format($state, $decimals) . ' ' . $currency;
        });
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    /** ISO 4217 currency code, e.g. 'EUR', 'USD', 'GBP'. */
    public function currency(string|Closure $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function decimals(int|Closure $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    /** ICU locale string, e.g. 'it_IT', 'en_US'. Defaults to app locale. */
    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Resolved getters (useful if you need to read values outside the column)
    // -------------------------------------------------------------------------

    public function getCurrency(): string
    {
        return (string) $this->evaluate($this->currency);
    }

    public function getDecimals(): string
    {
        return (int) $this->evaluate($this->decimals);
    }

    public function getLocale(): string
    {
        return $this->evaluate($this->locale) ?? app()->getLocale();
    }
}
