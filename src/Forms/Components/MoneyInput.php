<?php

namespace Wdog\FilamentUnusual\Forms\Components;

use Closure;
use NumberFormatter;
use Filament\Forms\Components\TextInput;

/**
 * A form field that displays and edits monetary values.
 *
 * The DB stores an integer (cents by default). Requires the PHP `intl`
 * extension with full ICU data (icu-data-full).
 *
 * Usage:
 *   MoneyInput::make('price')                    // EUR, cents
 *   MoneyInput::make('price')->currency('USD')
 *   MoneyInput::make('price')->divisor(1000)     // millicents → 3 decimal places
 *   MoneyInput::make('price')->locale('en_US')   // override locale
 */
class MoneyInput extends TextInput
{
    protected string|Closure $currency = 'EUR';

    protected int|Closure $divisor = 100;

    protected string|Closure|null $locale = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Hydrate: raw integer cents → formatted decimal string for display.
        // Guard against re-processing an already-formatted string (Filament may
        // call afterStateHydrated more than once in some configurations).
        $this->afterStateHydrated(function (mixed $state): void {
            if (blank($state)) {
                return;
            }

            // If already formatted (contains decimal separator), skip.
            if (is_string($state) && str_contains($state, $this->getDecimalSeparator())) {
                return;
            }

            $decimals  = $this->getDecimalPlaces();
            $amount    = (int) $state / $this->getDivisor();
            $formatter = $this->makeFormatter();
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

            $this->state($formatter->format($amount));
        });

        // Dehydrate: formatted decimal string → raw integer cents for storage.
        $this->dehydrateStateUsing(function (mixed $state): ?int {
            if (blank($state)) {
                return null;
            }

            $amount = $this->makeFormatter()->parse((string) $state);

            return $amount === false ? null : (int) round($amount * $this->getDivisor());
        });

        // Currency symbol as prefix.
        $this->prefix(fn (): string => $this->getCurrencySymbol());

        // Alpine $money mask: formats the input as the user types.
        // $money(value, decimalSeparator, thousandsSeparator, decimalPlaces)
        $this->extraInputAttributes(function (): array {
            $dec    = $this->getDecimalSeparator();
            $grp    = $dec === ',' ? '.' : ',';
            $places = $this->getDecimalPlaces();

            return [
                'x-mask:dynamic' => '$money($input, ' . json_encode($dec) . ', ' . json_encode($grp) . ", {$places})",
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    public function currency(string|Closure $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function divisor(int|Closure $divisor): static
    {
        $this->divisor = $divisor;

        return $this;
    }

    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Resolved getters
    // -------------------------------------------------------------------------

    public function getCurrency(): string
    {
        return (string) $this->evaluate($this->currency);
    }

    public function getDivisor(): int
    {
        return (int) $this->evaluate($this->divisor);
    }

    public function getLocale(): string
    {
        return $this->evaluate($this->locale) ?? app()->getLocale();
    }

    /** Number of decimal places = log10(divisor). */
    public function getDecimalPlaces(): int
    {
        return max(0, strlen((string) $this->getDivisor()) - 1);
    }

    /**
     * ICU locale used for NumberFormatter.
     * When no explicit locale is set, a representative locale is derived from
     * the currency code so that EUR always gets ',' as decimal separator.
     */
    public function getFormatterLocale(): string
    {
        if ($this->locale !== null) {
            return $this->getLocale();
        }

        $map = [
            'EUR' => 'de_DE', 'SEK' => 'sv_SE', 'NOK' => 'nb_NO',
            'DKK' => 'da_DK', 'PLN' => 'pl_PL', 'CZK' => 'cs_CZ',
            'HUF' => 'hu_HU', 'RON' => 'ro_RO', 'ISK' => 'is_IS',
            'BGN' => 'bg_BG', 'HRK' => 'hr_HR', 'RSD' => 'sr_RS',
            'BRL' => 'pt_BR', 'RUB' => 'ru_RU',
        ];

        return $map[$this->getCurrency()] ?? $this->getLocale();
    }

    public function getDecimalSeparator(): string
    {
        return $this->makeFormatter()->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    public function getCurrencySymbol(): string
    {
        $formatter = new NumberFormatter(
            $this->getFormatterLocale() . '@currency=' . $this->getCurrency(),
            NumberFormatter::CURRENCY,
        );

        $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        return blank($symbol) ? $this->getCurrency() : $symbol;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function makeFormatter(): NumberFormatter
    {
        return new NumberFormatter($this->getFormatterLocale(), NumberFormatter::DECIMAL);
    }
}
