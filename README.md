# 🦴 wdog/filament-unusual

Extra Filament components and Artisan commands for applications using [Filament Shield](https://github.com/bezhanSalleh/filament-shield).

## 📑 Contents

- [⚙️ Requirements](#️-requirements)
- [🚀 Installation](#-installation)
- [🧩 Components](#-components)
  - [🔐 RolePermissionsSummary](#-rolePermissionssummary)
  - [📅 DatePickerColumn](#-datepickercolumn)
  - [💶 MoneyInput](#-moneyinput)
  - [💶 MoneyCast](#-moneycast)
- [🛠️ Commands](#️-commands)
  - [shield:prune-permissions](#shieldprune-permissions)

---

## ⚙️ Requirements

- PHP 8.2+
- Filament v5
- bezhansalleh/filament-shield ^4.1
- spatie/laravel-permission ^6.0
- PHP `intl` extension with full ICU data (required by `MoneyInput`)

## 🚀 Installation

### 1. Add the path repository to `composer.json`

```json
"repositories": [
    {
        "type": "path",
        "url": "./packages/filament-unusual",
        "options": {
            "symlink": true
        }
    }
]
```

### 2. Require the package

```bash
composer require wdog/filament-unusual:@dev
```

### 3. Register the plugin in your Panel Provider

```php
use Wdog\FilamentUnusual\FilamentUnusualPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            FilamentUnusualPlugin::make(),
        ]);
}
```

---

## 🧩 Components

### 🔐 `RolePermissionsSummary`

A read-only Filament form component that displays a permission matrix for the roles currently selected in the form. It reads the `roles` field value reactively and shows which actions are granted per resource.

**Usage**

```php
use Wdog\FilamentUnusual\Forms\Components\RolePermissionsSummary;

Section::make('Permissions')
    ->schema([
        Select::make('roles')
            ->relationship('roles', 'name')
            ->multiple()
            ->preload()
            ->live(),

        RolePermissionsSummary::make(),
    ]);
```

**What it shows**

| Resource | view any | view | create | update | delete |
|----------|----------|------|--------|--------|--------|
| Role     | ✓        | ✓    | ✗      | ✗      | ✗      |
| User     | ✓        | ✓    | ✓      | ✓      | ✗      |

- Column order follows the canonical order defined in `config/filament-shield.php` (`policies.methods` + per-resource overrides in `resources.manage`).
- Permissions are parsed from the Filament Shield format (`ViewAny:Role` → resource `Role`, action `view any`).
- Permissions not matching the Shield format are grouped under `Other`.

> **Notes**
> - The component expects the form to have a `roles` field (a multi-select of role IDs).
> - It extends `Filament\Infolists\Components\Entry` and is purely read-only.
> - The default component name is `permissions_summary`.

---

### 📅 `DatePickerColumn`

An editable Filament table column that shows the date as plain text with a small pencil icon on hover. Clicking the pencil reveals an `<input type="date">` (the native date picker opens automatically) with a ✓ save button and ✗ cancel button. Confirming saves directly to the database via Livewire without page navigation or a modal.

**UX flow:** `date label 🖊` → click pencil → calendar opens + `[date input] ✓ ✗` → click ✓ → saved, back to label

**Icon colors**

| Icon | Color | Meaning |
|------|-------|---------|
| ✏ pencil | warning (amber) | Edit |
| ✓ checkmark | success (green) | Confirm / Save |
| ✗ X | danger (red) | Cancel |

**Namespace:** `Wdog\FilamentUnusual\Tables\Columns\DatePickerColumn`

**Usage**

```php
use Wdog\FilamentUnusual\Tables\Columns\DatePickerColumn;

DatePickerColumn::make('published_at')
    ->label('Published')
    ->sortable(),
```

**Available methods**

| Method | Description |
|--------|-------------|
| `displayFormat(string\|Closure)` | PHP date format for the human-readable label. Defaults to `d/m/Y`. |
| `timezone(string\|Closure)` | Timezone used when parsing the stored value. Defaults to `config('app.timezone')`. |
| `rules(array\|Closure)` | Validation rules applied before saving. Defaults to `['date']`. |
| `updateStateUsing(Closure)` | Override the default Eloquent save with a custom callback. Receives `$state` (Y-m-d string or `null`). |
| `beforeStateUpdated(Closure)` | Hook called before the value is saved. |
| `afterStateUpdated(Closure)` | Hook called after the value is saved. |
| `disabled(bool\|Closure)` | Shows date as read-only text, no pencil icon. |

**Keyboard shortcuts (when input is focused)**

| Key | Action |
|-----|--------|
| `Enter` | Save |
| `Escape` | Cancel |

**Examples**

```php
// Basic usage
DatePickerColumn::make('due_date'),

// Custom display format and timezone
DatePickerColumn::make('expires_at')
    ->displayFormat('d M Y')   // e.g. "14 Mar 2026"
    ->timezone('Europe/Rome')
    ->rules(['date', 'after:today']),

// Custom save logic
DatePickerColumn::make('published_at')
    ->updateStateUsing(function (?string $state, Model $record) {
        $record->publish($state ? Carbon::parse($state) : null);
    }),

// Disabled for non-admins
DatePickerColumn::make('locked_until')
    ->disabled(fn () => ! auth()->user()->isAdmin()),
```

> **Notes**
> - The stored value can be any format parseable by Carbon (e.g. `Y-m-d`, `Y-m-d H:i:s`, ISO 8601). The column always passes `Y-m-d` to the browser and back to the server.
> - The column reuses Filament's `fi-input` and `fi-input-wrp` CSS classes so it inherits the panel's existing input styling automatically.
> - Requires the `FilamentUnusualPlugin` to be registered so the Alpine JS component is loaded.

---

### 💶 `MoneyInput`

A Filament form field for monetary values. Renders a locale-aware formatted input with a currency symbol prefix and an Alpine.js `$money` mask that formats the number as the user types. The stored value is always a plain `float`; formatting is applied only for display.

> ⚠️ **Requires the PHP `intl` extension with full ICU data.**
> On Debian/Ubuntu: `apt install php-intl icu-devtools`. On Alpine (Docker): `apk add icu-data-full`.
> Without full ICU data, locale-specific separators and currency symbols may fall back to generic defaults (e.g. `¤` instead of `€`).

**Namespace:** `Wdog\FilamentUnusual\Forms\Components\MoneyInput`

**Usage**

```php
use Wdog\FilamentUnusual\Forms\Components\MoneyInput;

MoneyInput::make('price'),

MoneyInput::make('price')
    ->currency('USD')
    ->locale('en_US')
    ->decimals(2),
```

**Available methods**

| Method | Default | Description |
|--------|---------|-------------|
| `currency(string\|Closure)` | `'EUR'` | ISO 4217 currency code. Controls the symbol prefix and the `formatCurrency()` call. |
| `locale(string\|Closure\|null)` | app locale | ICU locale string (e.g. `'it_IT'`, `'en_US'`). Determines separators and symbol format. |
| `decimals(int\|Closure)` | `2` | Number of decimal places shown in the mask and stored value. |

**ICU data and localization**

The currency symbol and separators are resolved via PHP's `NumberFormatter` (ICU). The locale extension syntax `it_IT@currency=EUR` is used internally so that `CURRENCY_SYMBOL` returns the actual glyph (`€`) rather than the generic placeholder (`¤`).

If ICU data is incomplete, the field falls back to the ISO currency code as the prefix (e.g. `EUR`).

> **Notes**
> - The field always stores a `float` (or `null` for empty input). Cast your model attribute as `float` or `decimal`.
> - Grouping separators are stripped on dehydration; only the decimal separator is used to parse the raw value back to a float.
> - The Alpine mask is applied client-side via `$money()` (Filament's built-in Alpine money mask).

---

### 💶 `MoneyCast`

An Eloquent cast for monetary values stored as integers in the database (e.g. cents). The model exposes the value as a `float` in major units; the cast handles the conversion transparently on read and write.

**Namespace:** `Wdog\FilamentUnusual\Casts\MoneyCast`

**🤔 Why store money as integers?**

Floating-point arithmetic is imprecise: `0.1 + 0.2` in PHP equals `0.30000000000000004`, not `0.3`. For monetary values this causes rounding errors that silently corrupt totals. Storing amounts as integers (cents) eliminates the problem entirely — integer arithmetic is exact.

```
€12.50  →  stored as  1250  (cents, integer, safe ✅)
€12.50  →  stored as  12.5  (float, unsafe ❌ — do not do this)
```

**Usage**

```php
use Wdog\FilamentUnusual\Casts\MoneyCast;

protected function casts(): array
{
    return [
        'price'  => MoneyCast::class,           // default ÷ 100 (cents)
        'amount' => MoneyCast::class . ':100',  // explicit cents
        'tokens' => MoneyCast::class . ':1000', // millicents or other sub-units
    ];
}
```

**Conversion**

| Direction | Example |
|-----------|---------|
| DB → model (get) | `1250` → `12.50` |
| Model → DB (set) | `12.50` → `1250` |

The `set` side accepts floats, integers, and localised strings. Thousands separators (`.`, space, NBSP) are stripped; commas are treated as decimal separators.

**🔗 Full example — model + form + table**

```php
// 1. Migration — store cents as integer
$table->unsignedInteger('price'); // e.g. 1250 = €12.50

// 2. Model — cast cents ↔ float automatically
use Wdog\FilamentUnusual\Casts\MoneyCast;

protected function casts(): array
{
    return ['price' => MoneyCast::class];
}

// 3. Filament resource form — edit the value as a formatted input
use Wdog\FilamentUnusual\Forms\Components\MoneyInput;

MoneyInput::make('price')->currency('EUR')->locale('it_IT'),

// 4. Filament resource table — display the value as a formatted string
use Wdog\FilamentUnusual\Tables\Columns\MoneyColumn;

MoneyColumn::make('price')->currency('EUR')->locale('it_IT'),
```

`MoneyCast` sits in the middle: the DB column holds `1250`, the model exposes `12.50`, and both `MoneyInput` and `MoneyColumn` receive the float and format it for the user.

---

## 🛠️ Commands

### `shield:prune-permissions`

Deletes permissions from the database that are no longer registered in Filament Shield (orphaned permissions). Useful after removing resources, pages, or widgets.

```bash
php artisan shield:prune-permissions
```

**Options**

| Option | Description |
|--------|-------------|
| `--panel=<id>` | Panel ID to resolve entities from. Defaults to the first registered panel. |
| `--dry-run` | Lists orphaned permissions without deleting them. |

**Examples**

```bash
# Preview what would be deleted
php artisan shield:prune-permissions --dry-run

# Delete orphaned permissions for a specific panel
php artisan shield:prune-permissions --panel=admin

# Delete all orphaned permissions (default panel)
php artisan shield:prune-permissions
```

**What it considers valid**

The command collects all valid permission names from:
- Shield resources and their policy methods
- Shield pages
- Shield widgets
- Custom permissions defined in `config/filament-shield.php`

Any permission in the database that does not appear in this set is considered orphaned and will be deleted.
