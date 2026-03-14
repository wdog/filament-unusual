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

class DatePickerColumn extends Column implements Editable, HasEmbeddedView
{
    use CanBeValidated;
    use CanUpdateState;
    use HasExtraInputAttributes;

    protected string|Closure|null $displayFormat = null;

    protected string|Closure|null $timezone = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabledClick();

        $this->rules(['date']);
    }

    public function displayFormat(string|Closure|null $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    public function timezone(string|Closure|null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getDisplayFormat(): string
    {
        return $this->evaluate($this->displayFormat) ?? 'd/m/Y';
    }

    public function getTimezone(): string
    {
        return $this->evaluate($this->timezone) ?? config('app.timezone', 'UTC');
    }

    /**
     * Returns the date formatted as Y-m-d for the HTML date input.
     */
    public function getFormattedState(): string
    {
        $state = $this->getState();

        if (blank($state)) {
            return '';
        }

        try {
            return Carbon::parse($state, $this->getTimezone())->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Returns the date formatted for human display (e.g. 14/03/2026).
     */
    public function getHumanState(): string
    {
        $state = $this->getState();

        if (blank($state)) {
            return '—';
        }

        try {
            return Carbon::parse($state, $this->getTimezone())->format($this->getDisplayFormat());
        } catch (\Throwable) {
            return '—';
        }
    }

    public function toEmbeddedHtml(): string
    {
        $isDisabled = $this->isDisabled();
        $state      = $this->getFormattedState();
        $humanState = $this->getHumanState();

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
                'x-on:click.stop'             => '',
            ], escape: false)
            ->class(['fi-input text-sm']);

        ob_start(); ?>

        <div
            wire:ignore.self
            <?= $attributes->toHtml() ?>
        >
            <input type="hidden" value="<?= e($state) ?>" x-ref="serverState" />

            <?php if ( ! $isDisabled) { ?>

                <div
                    x-show="!isEditing"
                    class="flex items-center gap-1.5 group"
                >
                    <span class="text-sm text-gray-950 dark:text-white">
                        <?= e($humanState) ?>
                    </span>

                    <button
                        type="button"
                        x-on:click.stop="startEditing()"
                        class="opacity-0 group-hover:opacity-100 transition-opacity text-warning-400 hover:text-warning-500 dark:text-warning-500 dark:hover:text-warning-400"
                        title="Edit date"
                    >
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343z" />
                        </svg>
                    </button>
                </div>

                <div
                    x-show="isEditing"
                    x-on:click.stop
                    style="display:none"
                    class="flex items-center gap-1"
                >
                    <div
                        x-bind:class="{
                            'fi-disabled': isLoading,
                            'fi-invalid': error !== undefined,
                        }"
                        x-tooltip="
                            error === undefined
                                ? false
                                : {
                                    content: error,
                                    theme: $store.theme,
                                }
                        "
                        class="fi-input-wrp"
                    >
                        <div class="fi-input-wrp-content-ctn">
                            <input <?= $inputAttributes->toHtml() ?> />
                        </div>
                    </div>

                    <button
                        type="button"
                        x-on:click.stop="save()"
                        x-bind:disabled="isLoading"
                        class="flex items-center justify-center rounded-md p-1 text-success-500 hover:text-success-600 hover:bg-success-50 dark:text-success-400 dark:hover:text-success-300 dark:hover:bg-success-950 disabled:opacity-50 transition-colors"
                        title="Save"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        x-on:click.stop="cancelEditing()"
                        x-bind:disabled="isLoading"
                        class="flex items-center justify-center rounded-md p-1 text-danger-400 hover:text-danger-600 hover:bg-danger-50 dark:text-danger-500 dark:hover:text-danger-400 dark:hover:bg-danger-950 disabled:opacity-50 transition-colors"
                        title="Cancel"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>

            <?php } else { ?>

                <span class="text-sm text-gray-950 dark:text-white">
                    <?= e($humanState) ?>
                </span>

            <?php } ?>

        </div>

        <?php return ob_get_clean();
    }
}
