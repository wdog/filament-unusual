<?php

namespace Wdog\FilamentUnusual\Forms\Components;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Utilities\Get;

class RolePermissionsSummary extends Entry
{
    protected string $view = 'filament-unusual::forms.components.role-permissions-summary';

    /**
     * Returns the canonical display order for action columns, derived from the
     * Shield config (policies.methods + per-resource overrides in resources.manage).
     *
     * @return string[]
     */
    private static function actionOrder(): array
    {
        $globalMethods = config('filament-shield.policies.methods', []);

        $resourceMethods = collect(config('filament-shield.resources.manage', []))
            ->flatMap(fn (array $methods): array => $methods)
            ->unique();

        return collect($globalMethods)
            ->merge($resourceMethods)
            ->unique()
            ->map(fn (string $method): string => (string) Str::of($method)->kebab()->replace('-', ' '))
            ->values()
            ->all();
    }

    public static function getDefaultName(): ?string
    {
        return 'permissions_summary';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Permissions granted by selected roles')
            ->state(function (Get $get): ?Collection {
                $roleIds = $get('roles');

                if (empty($roleIds)) {
                    return null;
                }

                $names = Role::whereIn('id', $roleIds)
                    ->with('permissions')
                    ->get()
                    ->flatMap(fn (Role $role) => $role->permissions)
                    ->unique('id')
                    ->pluck('name');

                if ($names->isEmpty()) {
                    return collect();
                }

                return $names
                    ->map(fn (string $name) => $this->parsePermission($name))
                    ->groupBy('resource')
                    ->sortKeys()
                    ->map(fn (Collection $actions) => $actions
                        ->pluck('action')
                        ->sortBy(function (string $a): int {
                            $order = self::actionOrder();
                            $pos   = array_search($a, $order, true);

                            return $pos !== false ? $pos : 99;
                        })
                        ->values()
                    );
            });
    }

    /**
     * Returns the unique action columns for the current state, known actions
     * first (in canonical order), custom/unknown actions appended after.
     *
     * @return string[]
     */
    public function getPermissionColumns(): array
    {
        $groups = $this->getState();

        if ( ! $groups || $groups->isEmpty()) {
            return [];
        }

        $allActions = $groups->flatten()->unique()->values();

        $order  = self::actionOrder();
        $known  = collect($order)->filter(fn (string $a) => $allActions->contains($a));
        $custom = $allActions->reject(fn (string $a) => in_array($a, $order, true));

        return $known->merge($custom)->values()->all();
    }

    /**
     * Parses Filament Shield format: "ViewAny:Role" → {resource: "Role", action: "view any"}
     *
     * @return array{resource: string, action: string}
     */
    private function parsePermission(string $permission): array
    {
        if (str_contains($permission, ':')) {
            [$action, $resource] = explode(':', $permission, 2);

            return [
                'resource' => $resource,
                'action'   => (string) Str::of($action)->kebab()->replace('-', ' '),
            ];
        }

        return ['resource' => 'Other', 'action' => $permission];
    }
}
