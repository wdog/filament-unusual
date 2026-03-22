<?php

namespace Wdog\FilamentUnusual\Tables\Columns;

use Closure;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\Column;
use Filament\Support\Components\Contracts\HasEmbeddedView;

/**
 * A read-only table column that displays a numeric value (0–100) as a
 * coloured horizontal progress bar with the percentage label centred on it.
 *
 * Usage:
 *   PercentageColumn::make('completion')
 *   PercentageColumn::make('score')->color('primary')
 *   PercentageColumn::make('score')->color(fn ($state) => $state >= 75 ? 'success' : 'danger')
 *   PercentageColumn::make('score')->decimals(1)
 */
class PercentageColumn extends Column implements HasEmbeddedView
{
    /**
     * Filament semantic colour name ('primary', 'success', 'warning', 'danger',
     * 'info', 'gray') or any raw CSS colour value (hex, rgb, etc.).
     * null = automatic colour based on the value (red → amber → green).
     */
    protected string|Closure|null $color = null;

    /** Decimal places shown in the label. */
    protected int|Closure $decimals = 0;

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    /**
     * Bar fill colour.
     * Accepts a Filament semantic colour name or any CSS colour.
     * When null (default) the colour is chosen automatically:
     *   0–33 % → danger, 34–66 % → warning, 67–100 % → success.
     */
    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /** Number of decimal places shown in the percentage label. */
    public function decimals(int|Closure $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Resolved getters
    // -------------------------------------------------------------------------

    public function getDecimals(): int
    {
        return (int) $this->evaluate($this->decimals);
    }

    /**
     * Returns an inline CSS colour value for the bar fill.
     * Filament semantic colour names are mapped to their CSS custom property
     * equivalents so the bar integrates with the active panel theme.
     */
    public function getBarColor(float $percentage): string
    {
        $color = $this->evaluate($this->color);

        if ($color !== null) {
            return $this->resolveColor((string) $color);
        }

        return match (true) {
            $percentage >= 67 => $this->resolveColor('success'),
            $percentage >= 34 => $this->resolveColor('warning'),
            default           => $this->resolveColor('danger'),
        };
    }

    private function resolveColor(string $color): string
    {
        // Filament semantic names → panel CSS custom properties.
        $semantic = match ($color) {
            'primary' => 'var(--color-primary-500)',
            'success' => 'var(--color-success-500)',
            'warning' => 'var(--color-warning-500)',
            'danger'  => 'var(--color-danger-500)',
            'info'    => 'var(--color-info-500)',
            'gray'    => 'var(--color-gray-400)',
            default   => null,
        };

        if ($semantic !== null) {
            return $semantic;
        }

        // Already a CSS value: hex (#6366f1), functional (rgb(), hsl()), or var().
        if (str_starts_with($color, '#') || str_starts_with($color, 'var(') || str_contains($color, '(')) {
            return $color;
        }

        // Tailwind color name without shade (e.g. 'green') → shade 500.
        // Tailwind color name with shade (e.g. 'green-700') → that exact shade.
        if (preg_match('/^[a-z]+(-\d+)?$/', $color)) {
            return str_contains($color, '-')
                ? "var(--color-{$color})"
                : "var(--color-{$color}-500)";
        }

        return $color;
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /**
     * `HasEmbeddedView` contract: returns the full HTML string for the cell.
     *
     * The bar consists of three layers:
     *   1. Track — the full-width grey background.
     *   2. Fill  — the coloured layer whose width equals the percentage.
     *   3. Label — the centred "54 %" text, always visible on top.
     */
    public function toEmbeddedHtml(): string
    {
        $state    = $this->getState();
        $pct      = blank($state) ? 0.0 : (float) $state;
        $pct      = max(0.0, min(100.0, $pct));
        $decimals = $this->getDecimals();
        $label    = number_format($pct, $decimals) . '%';
        $color    = $this->getBarColor($pct);
        $width    = number_format($pct, 2, '.', '') . '%';

        ob_start(); ?>

        <div class="pct-bar-track">
            <div class="pct-bar-fill" style="width: <?= e($width) ?>; background-color: <?= e($color) ?>;"></div>
            <span class="pct-bar-label"><?= e($label) ?></span>
        </div>

<?php return ob_get_clean();
    }
}
