<?php

namespace Wdog\FilamentUnusual\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;

class PrunePermissions extends Command
{
    protected $signature = 'shield:prune-permissions
        {--panel= : Panel ID to resolve entities from (defaults to first panel)}
        {--dry-run : List orphaned permissions without deleting them}';

    protected $description = 'Delete permissions from the database that are no longer registered in Filament Shield.';

    public function handle(): int
    {
        $panelId = $this->option('panel') ?? collect(Filament::getPanels())->keys()->first();
        Filament::setCurrentPanel(Filament::getPanel($panelId));

        $valid = collect();

        $valid = $valid->merge(
            collect(FilamentShield::getResources())
                ->flatMap(fn (array $r) => FilamentShield::getResourcePermissions($r['resourceFqcn']))
        );

        $valid = $valid->merge(
            collect(FilamentShield::getPages())
                ->map(fn (array $p) => array_key_first($p['permissions']))
        );

        $valid = $valid->merge(
            collect(FilamentShield::getWidgets())
                ->map(fn (array $w) => array_key_first($w['permissions']))
        );

        $valid = $valid->merge(
            collect(FilamentShield::getCustomPermissions())->keys()
        );

        $orphans = Permission::whereNotIn('name', $valid->all())->get();

        if ($orphans->isEmpty()) {
            $this->components->info('No orphaned permissions found.');

            return self::SUCCESS;
        }

        $this->table(
            ['#', 'Permission'],
            $orphans->values()->map(fn (Permission $p, int $i) => [
                '#'          => $i + 1,
                'Permission' => $p->name,
            ])
        );

        if ($this->option('dry-run')) {
            $this->components->warn("Dry-run: {$orphans->count()} orphaned permission(s) listed above — nothing deleted.");

            return self::SUCCESS;
        }

        Permission::whereIn('id', $orphans->pluck('id'))->delete();

        $this->components->success("Pruned {$orphans->count()} orphaned permission(s).");

        return self::SUCCESS;
    }
}
