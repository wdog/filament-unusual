<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php $groups = $getState(); @endphp

    @if ($groups === null)

        {{-- No roles selected --}}
        <div class="rp-empty-state">
            <x-filament::icon icon="heroicon-o-shield-exclamation" class="rp-state-icon" />
            <span class="italic">Select a role to see its permissions.</span>
        </div>

    @elseif ($groups->isEmpty())

        {{-- Roles selected but none have permissions --}}
        <div class="rp-warning-state">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="rp-state-icon" />
            <span class="italic">No permissions assigned to the selected roles.</span>
        </div>

    @else
        @php $columns = $getPermissionColumns(); @endphp

        <div class="rp-table-wrapper">
            <table class="rp-table">
                <thead>
                    <tr class="rp-thead-row">
                        <th class="rp-th-resource">Resource</th>
                        @foreach ($columns as $col)
                            <th class="rp-th-action">{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $resource => $actions)
                        <tr @class([
                            'rp-tr-separator' => ! $loop->first,
                            'rp-tr-odd'       => $loop->odd,
                            'rp-tr-even'      => $loop->even,
                        ])>
                            <td class="rp-td-resource">{{ $resource }}</td>
                            @foreach ($columns as $col)
                                <td class="rp-td-action">
                                    @if ($actions->contains($col))
                                        <x-filament::icon icon="heroicon-s-check-circle" class="rp-icon-allow" />
                                    @else
                                        <x-filament::icon icon="heroicon-s-x-mark" class="rp-icon-deny" />
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="rp-tfoot-row">
                        <td colspan="{{ count($columns) + 1 }}" class="rp-tfoot-cell">
                            {{ $groups->sum(fn($a) => $a->count()) }} permissions
                            across {{ $groups->count() }} {{ Str::plural('resource', $groups->count()) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

    @endif
</x-dynamic-component>
