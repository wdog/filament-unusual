# wdog/filament-unusual

Extra Filament components and Artisan commands for applications using [Filament Shield](https://github.com/bezhanSalleh/filament-shield).

## Requirements

- PHP 8.2+
- Filament v5
- bezhansalleh/filament-shield ^4.1
- spatie/laravel-permission ^6.0

## Installation

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

## Components

### `RolePermissionsSummary`

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

**Notes**
- The component expects the form to have a `roles` field (a multi-select of role IDs).
- It extends `Filament\Infolists\Components\Entry` and is purely read-only.
- The default component name is `permissions_summary`.

---

### `DatePickerColumn`

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

**Notes**
- The stored value can be any format parseable by Carbon (e.g. `Y-m-d`, `Y-m-d H:i:s`, ISO 8601). The column always passes `Y-m-d` to the browser and back to the server.
- The column reuses Filament's `fi-input` and `fi-input-wrp` CSS classes so it inherits the panel's existing input styling automatically.
- Requires the `FilamentUnusualPlugin` to be registered so the Alpine JS component is loaded.

---

## Commands

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
