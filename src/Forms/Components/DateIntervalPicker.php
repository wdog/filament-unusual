<?php

namespace Wdog\FilamentUnusual\Forms\Components;

use Closure;
use Carbon\Carbon;
use Illuminate\Support\Js;
use Filament\Forms\Components\Field;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Components\Contracts\HasEmbeddedView;

/**
 * An inline date-navigation field for Filament forms.
 *
 * Renders as:  [‹]  [lunedì 14 luglio 2025]  [›]
 *
 * The visible text input is read-only and shows a human-readable, locale-aware
 * date. Navigation is only possible via the ‹ / › buttons. The stored value
 * format depends on the step:
 *   day / week  → Y-m-d  (week always stores the Monday of the selected week)
 *   month       → Y-m
 *   year        → Y
 *
 * Architecture:
 *   - Extends Field so Filament handles state, validation, and layout automatically.
 *   - Implements HasEmbeddedView to render everything from PHP without a Blade file.
 *   - A hidden <input wire:model> keeps Livewire's deferred state in sync.
 *     When ‹/› are clicked, Alpine writes the new value to the hidden input and
 *     dispatches an 'input' event — Livewire queues the update for the next request
 *     without triggering an extra network round-trip.
 *   - $wire.$entangle() is intentionally NOT used: reassigning the proxy object
 *     from inside a constructor function breaks the entanglement binding.
 */
class DateIntervalPicker extends Field implements HasEmbeddedView
{
    /**
     * ICU locale string used for the human-readable display (e.g. 'it', 'en', 'fr').
     * Defaults to the application locale.
     */
    protected string|Closure|null $locale = null;

    /**
     * Navigation step: 'day' | 'week' | 'month' | 'year'.
     * Controls how much the date shifts when clicking < / >.
     */
    protected string|Closure $step = 'day';

    /**
     * Carbon isoFormat token string for the visible display text.
     * When null, a sensible default is derived from the current step:
     *   day   → 'DD/MM/YYYY'          e.g. 12/07/2024
     *   week  → '__WEEK__' (special)  e.g. 12 - 18 lug 2024
     *   month → 'MMMM YYYY'           e.g. luglio 2024
     *   year  → 'YYYY'                e.g. 2024
     */
    protected string|Closure|null $displayFormat = null;

    /** PHP Carbon format used when serializing/reading the state value per step. */
    private const STEP_VALUE_FORMATS = [
        'day'   => 'Y-m-d',
        'week'  => 'Y-m-d',
        'month' => 'Y-m',
        'year'  => 'Y',
    ];

    /** Per-step format defaults. '__WEEK__' is handled specially in both PHP and JS. */
    private const STEP_DEFAULTS = [
        'day'   => 'DD/MM/YYYY',
        'week'  => '__WEEK__',
        'month' => 'MMMM YYYY',
        'year'  => 'YYYY',
    ];

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure an empty string from the input is stored as null, not "".
        // The serialization format depends on the step (day/week → Y-m-d, month → Y-m, year → Y).
        $this->dehydrateStateUsing(function (mixed $state): ?string {
            if (blank($state)) {
                return null;
            }

            try {
                $carbon = Carbon::parse($state);
                $step   = $this->getStep();

                if ($step === 'week') {
                    $carbon = $carbon->startOfWeek();
                }

                return $carbon->format(self::STEP_VALUE_FORMATS[$step] ?? 'Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        });
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    /**
     * Carbon isoFormat token string for the visible display text.
     * Examples:
     *   'dddd D MMMM YYYY'  →  venerdì 12 luglio 2024  (default)
     *   'DD/MM/YYYY'        →  12/07/2024
     *   'D MMM YYYY'        →  12 lug 2024
     */
    public function displayFormat(string|Closure $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    /** ICU locale string for the display format (e.g. 'it', 'en', 'fr'). */
    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Navigation step: 'day' (default), 'week', or 'month'.
     * Controls how far < / > buttons move the date.
     */
    public function step(string|Closure $step): static
    {
        $this->step = $step;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Resolved getters
    // -------------------------------------------------------------------------

    public function getLocale(): string
    {
        return $this->evaluate($this->locale) ?? app()->getLocale();
    }

    public function getStep(): string
    {
        return (string) $this->evaluate($this->step);
    }

    public function getDisplayFormat(): string
    {
        $fmt = $this->evaluate($this->displayFormat);

        if (! blank($fmt)) {
            return (string) $fmt;
        }

        return self::STEP_DEFAULTS[$this->getStep()] ?? 'DD/MM/YYYY';
    }

    /**
     * Returns the state normalized to a Y-m-d string (or empty string).
     * Handles Carbon instances, DateTime objects, and plain strings safely.
     */
    public function getFormattedState(): string
    {
        $state = $this->getState();

        if (blank($state)) {
            return '';
        }

        try {
            $fmt = self::STEP_VALUE_FORMATS[$this->getStep()] ?? 'Y-m-d';
            return Carbon::parse($state)->format($fmt);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Returns the initial human-readable display string for the current state.
     * Computed server-side so the input is never blank on first render.
     */
    public function getDisplayText(): string
    {
        $formatted = $this->getFormattedState();

        if ($formatted === '') {
            return '';
        }

        try {
            $carbon = Carbon::parse($formatted)->locale($this->getLocale());
            $fmt    = $this->getDisplayFormat();

            if ($fmt === '__WEEK__') {
                return $this->formatWeekRange($carbon);
            }

            return $carbon->isoFormat($fmt);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Format a Carbon date as a week range, e.g.:
     *   "12 - 18 lug 2024"          (same month)
     *   "28 lug - 3 ago 2024"       (different months, same year)
     *   "30 dic 2024 - 5 gen 2025"  (different years)
     */
    private function formatWeekRange(Carbon $date): string
    {
        $from = $date->copy()->startOfWeek();
        $to   = $date->copy()->endOfWeek();

        $fromDay  = $from->isoFormat('D');
        $toDay    = $to->isoFormat('D');
        $fromMon  = $from->isoFormat('MMM');
        $toMon    = $to->isoFormat('MMM');
        $fromYear = $from->year;
        $toYear   = $to->year;

        if ($fromYear !== $toYear) {
            return "{$fromDay} {$fromMon} {$fromYear} - {$toDay} {$toMon} {$toYear}";
        }

        if ($fromMon !== $toMon) {
            return "{$fromDay} {$fromMon} - {$toDay} {$toMon} {$fromYear}";
        }

        return "{$fromDay} - {$toDay} {$toMon} {$fromYear}";
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    public function toEmbeddedHtml(): string
    {
        $statePath   = $this->getStatePath();
        $state       = $this->getFormattedState();
        $displayText = $this->getDisplayText();
        $isDisabled  = $this->isDisabled();

        $attributes = $this->getExtraAttributeBag()
            ->merge([
                'x-load'     => true,
                'x-load-src' => FilamentAsset::getAlpineComponentSrc('forms/date-interval-picker', 'wdog/filament-unusual'),
                'x-data'     => 'dateIntervalPicker({
                    initialState:  ' . Js::from($state) . ',
                    locale:        ' . Js::from($this->getLocale()) . ',
                    step:          ' . Js::from($this->getStep()) . ',
                    displayFormat: ' . Js::from($this->getDisplayFormat()) . ',
                })',
            ], escape: false)
            ->class(['date-interval-picker']);

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>

            <?php if ($isDisabled) { ?>

                <span class="date-interval-picker-display-disabled">
                    <?= e($displayText) ?>
                </span>

            <?php } else { ?>

                <!-- Hidden input keeps Livewire's deferred wire:model in sync. -->
                <input
                    type="hidden"
                    wire:model="<?= e($statePath) ?>"
                    x-ref="wireInput"
                    value="<?= e($state) ?>"
                />

                <div class="date-interval-picker-row">

                    <button
                        type="button"
                        x-on:click="prev()"
                        class="date-interval-picker-nav-btn"
                        title="Previous <?= e($this->getStep()) ?>">
                        <?= svg('heroicon-s-chevron-left', 'h-4 w-4')->toHtml() ?>
                    </button>

                    <input
                        type="text"
                        x-model="displayText"
                        readonly
                        class="date-interval-picker-input fi-input"
                    />

                    <button
                        type="button"
                        x-on:click="next()"
                        class="date-interval-picker-nav-btn"
                        title="Next <?= e($this->getStep()) ?>">
                        <?= svg('heroicon-s-chevron-right', 'h-4 w-4')->toHtml() ?>
                    </button>

                </div>

            <?php } ?>

        </div>

<?php return ob_get_clean();
    }
}
