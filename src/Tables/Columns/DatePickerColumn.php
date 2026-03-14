<?php

namespace Wdog\FilamentUnusual\Tables\Columns;

use Closure;
use Carbon\Carbon;
use Filament\Tables\Table;
use Illuminate\Support\Js;
use Filament\Tables\Columns\Column;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\Contracts\Editable;
use Filament\Tables\Columns\Concerns\CanBeValidated;
use Filament\Tables\Columns\Concerns\CanUpdateState;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;

/**
 * An inline-editable date column for Filament tables.
 *
 * Architecture:
 *   - Implements `Editable` so Filament knows it can accept user input.
 *   - Implements `HasEmbeddedView` so the HTML is rendered entirely in PHP
 *     via `toEmbeddedHtml()`, without a separate Blade file.
 *   - Uses `CanUpdateState` to wire the save action back to the Livewire
 *     component through the standard `updateTableColumnState` call.
 *   - Uses `CanBeValidated` to run server-side validation rules before saving.
 *   - The Alpine.js component (`datePickerTableColumn`) is loaded lazily via
 *     `x-load` / `x-load-src` so it doesn't block the initial page render.
 *
 * Color system:
 *   Button colors use Filament's own CSS infrastructure instead of dynamic
 *   Tailwind classes. `fi-color-{name}` (from utilities.css) sets `--color-*`
 *   CSS variables; the Tailwind utilities `text-(--color-*)` are static strings
 *   that the scanner always includes. No safelist or class map needed.
 */
class DatePickerColumn extends Column implements Editable, HasEmbeddedView
{
    use CanBeValidated;
    use CanUpdateState;
    use HasExtraInputAttributes;

    /** Human-readable format used when the cell is NOT being edited. */
    protected string|Closure|null $displayFormat = null;

    /** Timezone used when parsing and displaying dates. */
    protected string|Closure|null $timezone = null;

    /**
     * Column name whose value is used as the upper/lower bound for validation.
     * Resolved from the record at validation time so the comparison is always
     * against the current saved value, not a form state.
     */
    protected ?string $beforeOrEqualColumn = null;

    protected ?string $afterOrEqualColumn = null;

    /**
     * Whether the cell can be edited by the current user.
     * Evaluated at render time so a Closure receives `$record` and can check
     * a Gate policy: `->editable(fn (Lesson $record) => Gate::allows('update', $record))`.
     * When false, the cell falls back to the disabled (read-only) state.
     */
    protected bool|Closure $editable = true;

    /**
     * Filament color names for the three interactive buttons.
     * Accepted values: 'warning', 'success', 'danger', 'primary', 'info', 'gray'.
     */
    protected string|Closure $pencilColor = 'warning';

    protected string|Closure $acceptColor = 'success';

    protected string|Closure $cancelColor = 'danger';

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent Filament from toggling the edit mode on row click —
        // the pencil icon is the only intended trigger.
        $this->disabledClick();

        // Server-side validation so bad input is rejected before hitting the DB.
        $this->rules(['date']);
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    /** Validate that this date is ≤ the value of another column on the same record. */
    public function beforeOrEqual(string $column): static
    {
        $this->beforeOrEqualColumn = $column;

        return $this;
    }

    /** Validate that this date is ≥ the value of another column on the same record. */
    public function afterOrEqual(string $column): static
    {
        $this->afterOrEqualColumn = $column;

        return $this;
    }

    /**
     * Control whether this cell is editable.
     *
     * Accepts a static bool or a Closure. The Closure receives the Eloquent
     * record (by name `$record` or by type-hint), so you can delegate directly
     * to a Gate policy:
     *
     *   ->editable(fn (Lesson $record) => Gate::allows('update', $record))
     *   ->editable(fn ($record) => $record->user_id === auth()->id())
     */
    public function editable(bool|Closure $condition = true): static
    {
        $this->editable = $condition;

        return $this;
    }

    /** Format passed to Carbon::format() for the read-only display value. */
    public function displayFormat(string|Closure|null $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    /** Timezone used when parsing and displaying dates. */
    public function timezone(string|Closure|null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    /** Color of the pencil (edit trigger) icon. Default: 'warning'. */
    public function pencilColor(string|Closure $color): static
    {
        $this->pencilColor = $color;

        return $this;
    }

    /** Color of the save / accept button. Default: 'success'. */
    public function acceptColor(string|Closure $color): static
    {
        $this->acceptColor = $color;

        return $this;
    }

    /** Color of the cancel button. Default: 'danger'. */
    public function cancelColor(string|Closure $color): static
    {
        $this->cancelColor = $color;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Resolved getters
    // -------------------------------------------------------------------------

    public function getDisplayFormat(): string
    {
        return $this->evaluate($this->displayFormat) ?? 'd/m/Y';
    }

    public function getTimezone(): string
    {
        return $this->evaluate($this->timezone) ?? config('app.timezone', 'UTC');
    }

    public function getPencilColor(): string
    {
        return (string) $this->evaluate($this->pencilColor);
    }

    public function getAcceptColor(): string
    {
        return (string) $this->evaluate($this->acceptColor);
    }

    public function getCancelColor(): string
    {
        return (string) $this->evaluate($this->cancelColor);
    }

    /**
     * Appends cross-column date constraints to the base rules.
     * Called at validation time so `$this->getRecord()` is already populated.
     *
     * Note: cannot use parent::getRules() because CanBeValidated is a trait
     * used by this class, not by Column — so Column has no getRules() method.
     * The trait logic (evaluate + nullable fallback) is inlined here instead.
     */
    public function getRules(): array
    {
        // Replicate CanBeValidated::getRules() logic.
        $rules = (array) $this->evaluate($this->rules);

        if (! in_array('required', $rules)) {
            $rules[] = 'nullable';
        }

        if ($this->beforeOrEqualColumn !== null) {
            $value = $this->getRecord()?->{$this->beforeOrEqualColumn};

            if ($value) {
                $rules[] = 'before_or_equal:' . ($value instanceof Carbon ? $value->format('Y-m-d') : $value);
            }
        }

        if ($this->afterOrEqualColumn !== null) {
            $value = $this->getRecord()?->{$this->afterOrEqualColumn};

            if ($value) {
                $rules[] = 'after_or_equal:' . ($value instanceof Carbon ? $value->format('Y-m-d') : $value);
            }
        }

        return $rules;
    }

    // -------------------------------------------------------------------------
    // State helpers
    // -------------------------------------------------------------------------

    /**
     * Parses the column state into a Carbon instance.
     * Returns null if the state is blank or unparseable.
     *
     * Single parsing point — both display and input formats are derived from
     * this method to avoid duplicating the try/catch error handling.
     */
    private function parsedDate(): ?Carbon
    {
        $state = $this->getState();

        if (blank($state)) {
            return null;
        }

        try {
            return Carbon::parse($state, $this->getTimezone());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Date formatted as Y-m-d for the HTML <input type="date"> value attribute.
     * Returns an empty string when no date is set (keeps the input blank).
     */
    public function getFormattedState(): string
    {
        return $this->parsedDate()?->format('Y-m-d') ?? '';
    }

    /**
     * Date formatted for human display according to `displayFormat`.
     * Returns '—' (em dash) when no date is set, matching Filament's convention
     * for empty table cells.
     */
    public function getHumanState(): string
    {
        return $this->parsedDate()?->format($this->getDisplayFormat()) ?? '—';
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /**
     * `HasEmbeddedView` contract: returns the full HTML string for the cell.
     *
     * Two visual states:
     *   1. Read mode  — shows the human-readable date + a hover pencil button.
     *   2. Edit mode  — shows <input type="date"> + save / cancel buttons.
     *
     * The Alpine component (`datePickerTableColumn`) manages the toggle between
     * the two states entirely on the client, so the Livewire server is only
     * contacted when the user commits a change.
     *
     * `wire:ignore.self` prevents Livewire from re-rendering the wrapper div
     * while Alpine owns the DOM inside it — without this, saving would cause
     * a DOM patch that resets the Alpine state mid-interaction.
     */
    public function toEmbeddedHtml(): string
    {
        // isDisabled() covers ->disabled() calls; the editable check covers
        // permission/policy-based restrictions set via ->editable().
        $isDisabled = $this->isDisabled() || ! $this->evaluate($this->editable);
        $state      = $this->getFormattedState();
        $humanState = $this->getHumanState();

        // Outer wrapper attributes — carries the Alpine component definition
        // and the lazy-load directive for its JS module.
        $attributes = $this->getExtraAttributeBag()
            ->merge([
                'x-load'     => true,
                'x-load-src' => FilamentAsset::getAlpineComponentSrc('columns/date-picker', 'wdog/filament-unusual'),
                'x-data'     => 'datePickerTableColumn({
                    name: ' . Js::from($this->getName()) . ',
                    recordKey: ' . Js::from($this->getRecordKey()) . ',
                    state: ' . Js::from($state) . ',
                })',
            ], escape: false)
            ->class([
                'fi-ta-date-picker',
                'fi-inline' => $this->isInline(),
            ]);

        // Input element attributes — Alpine binds its reactive `editingState`
        // to the value; wire:loading.attr and wire:target disable the input
        // while a Livewire request is in flight.
        $inputAttributes = $this->getExtraInputAttributeBag()
            ->merge([
                'disabled'                    => $isDisabled,
                'wire:loading.attr'           => 'disabled',
                'wire:target'                 => implode(',', Table::LOADING_TARGETS),
                'x-bind:disabled'             => $isDisabled ? null : 'isLoading',
                'type'                        => 'date',
                'x-model'                     => 'editingState',
                'x-ref'                       => 'dateInput',
                'x-on:keydown.enter.prevent'  => 'save()',
                'x-on:keydown.escape.prevent' => 'cancelEditing()',
                'x-on:click.stop'             => '',  // prevent row-click from firing
            ], escape: false)
            ->class(['fi-input text-sm']);

        ob_start(); ?>

        <div
            wire:ignore.self
            <?= $attributes->toHtml() ?>>
            <!-- Hidden input holds the server's last-known value so Alpine can
                 detect unsaved changes and revert on cancel. -->
            <input type="hidden" value="<?= e($state) ?>" x-ref="serverState" />

            <?php if (! $isDisabled) { ?>

                <!-- READ MODE: visible when `isEditing` is false -->
                <div
                    x-show="!isEditing"
                    x-on:click.stop="startEditing()"
                    class="flex items-center gap-1.5 cursor-pointer group">
                    <span class="text-sm text-gray-950 dark:text-white">
                        <?= e($humanState) ?>
                    </span>

                    <!-- fi-color-{name} sets --color-* CSS vars (Filament utilities.css).
                         text-(--color-400) etc. are static strings → always scanned by Tailwind. -->
                    <button
                        type="button"

                        class="opacity-0 group-hover:opacity-100 transition-opacity fi-color-<?= e($this->getPencilColor()) ?> text-(--color-400) hover:text-(--color-500) dark:text-(--color-500) dark:hover:text-(--color-400)"
                        title="Edit date">
                        <!-- Pencil / edit icon (Heroicons mini) -->
                        <?= e(svg('heroicon-s-pencil', 'h-4 w-4')) ?>
                    </button>
                </div>

                <!-- EDIT MODE: hidden by default (style="display:none" avoids a
                     flash of the input before Alpine initialises). -->
                <div
                    x-show="isEditing"
                    x-on:click.stop
                    style="display:none"
                    class="flex flex-col gap-1">
                    <div class="flex items-center gap-1">
                        <!-- fi-input-wrp / fi-invalid / fi-disabled are Filament's own
                             CSS hooks so the input inherits panel theming automatically. -->
                        <div
                            x-bind:class="{
                                'fi-disabled': isLoading,
                                'fi-invalid': error !== undefined,
                            }"
                            class="fi-input-wrp">
                            <div class="fi-input-wrp-content-ctn">
                                <input <?= $inputAttributes->toHtml() ?> />
                            </div>
                        </div>

                        <!-- Save button -->
                        <button
                            type="button"
                            x-on:click.stop="save()"
                            x-bind:disabled="isLoading"
                            class="flex items-center justify-center rounded-md p-1 fi-color-<?= e($this->getAcceptColor()) ?> text-(--color-500) hover:text-(--color-600) hover:bg-(--color-50) dark:text-(--color-400) dark:hover:text-(--color-300) dark:hover:bg-(--color-950) disabled:opacity-50 transition-colors"
                            title="Save">
                            <!-- Check / confirm icon (Heroicons mini) -->
                            <?= e(svg('heroicon-s-check', 'h-4 w-4')) ?>
                        </button>

                        <!-- Cancel button -->
                        <button
                            type="button"
                            x-on:click.stop="cancelEditing()"
                            x-bind:disabled="isLoading"
                            class="flex items-center justify-center rounded-md p-1 fi-color-<?= e($this->getCancelColor()) ?> text-(--color-400) hover:text-(--color-600) hover:bg-(--color-50) dark:text-(--color-500) dark:hover:text-(--color-400) dark:hover:bg-(--color-950) disabled:opacity-50 transition-colors"
                            title="Cancel">
                            <!-- X / close icon (Heroicons mini) -->
                            <?= e(svg('heroicon-s-x-mark', 'h-4 w-4')) ?>
                        </button>
                    </div>

                    <!-- Validation error message -->
                    <p
                        x-show="error !== undefined"
                        x-text="error"
                        class="fi-fo-field-wrp-error-message
                        fi-color-danger text-(--color-400)
                        text-xs ">
                    </p>
                </div>

            <?php } else { ?>

                <!-- DISABLED STATE: just the formatted date, no interaction. -->
                <span class="text-sm text-gray-950 dark:text-white">
                    <?= e($humanState) ?>
                </span>

            <?php } ?>

        </div>

<?php return ob_get_clean();
    }
}
