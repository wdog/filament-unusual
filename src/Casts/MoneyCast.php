<?php

namespace Wdog\FilamentUnusual\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Eloquent cast for monetary values stored as integers in the database.
 *
 * The DB column stores the amount in the smallest currency unit (e.g. cents).
 * The model exposes it as a float in major units (e.g. euros).
 *
 * Usage in model casts():
 *   'amount' => MoneyCast::class,           // default: cents (÷ 100)
 *   'amount' => MoneyCast::class . ':100',  // explicit cents
 *   'amount' => MoneyCast::class . ':1000', // millicents
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(protected int $divider = 100) {}

    /**
     * Read: DB integer → model float.
     *
     * Example: 1250 → 12.50
     */
    public function get($model, string $key, $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        return ((float) $value) / $this->divider;
    }

    /**
     * Write: model value → DB integer.
     *
     * Accepts a float, an integer, or a localised string (e.g. "1.250,56").
     * Thousands separators (dot, space, NBSP) are stripped; commas are treated
     * as decimal separators and normalised to dots before conversion.
     *
     * Example: 12.50 → 1250 | "1.250,56" → 125056
     */
    public function set($model, string $key, $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(['.', ' ', "\u{00A0}"], '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (int) round(((float) $value) * $this->divider);
    }
}
