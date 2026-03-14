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

    /**
     * The number by which the raw DB value is divided before formatting.
     *   100  → value is stored in cents      (default)
     *   1000 → value is stored in millicents
     *   1    → value is already in major units
     */
    protected int|Closure $divisor = 100;

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

            $divisor  = $this->evaluate($this->divisor);
            $currency = (string) $this->evaluate($this->currency);
            $locale   = $this->evaluate($this->locale) ?? app()->getLocale();

            $amount = (int) $state / $divisor;

            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

            $formatted = $formatter->formatCurrency($amount, $currency);

            // NumberFormatter returns false on failure (e.g. unknown currency).
            return $formatted !== false ? $formatted : number_format($amount, 2) . ' ' . $currency;
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

    /**
     * Divisor applied to the raw DB integer before formatting.
     * Use 100 for cents (default), 1000 for millicents, 1 for major units.
     */
    public function divisor(int|Closure $divisor): static
    {
        $this->divisor = $divisor;

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

    public function getDivisor(): int
    {
        return $this->evaluate($this->divisor);
    }

    public function getLocale(): string
    {
        return $this->evaluate($this->locale) ?? app()->getLocale();
    }
}
