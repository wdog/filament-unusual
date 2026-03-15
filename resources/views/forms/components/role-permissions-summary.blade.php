<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php $groups = $getState(); @endphp

    @if ($groups === null)

        {{-- No roles selected --}}
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-4 w-4 shrink-0" />
            <span class="italic">Select a role to see its permissions.</span>
        </div>
    @elseif ($groups->isEmpty())
        {{-- Roles selected but none have permissions --}}
        <div class="flex items-center gap-2 text-sm text-warning-600 dark:text-warning-400">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 shrink-0" />
            <span class="italic">No permissions assigned to the selected roles.</span>
        </div>
    @else

        @php $columns = $getPermissionColumns(); @endphp

        <div class="w-full overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr class="rp-thead-row">
                        <th class="w-full px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-200">
                            Resource
                        </th>
                        @foreach ($columns as $col)
                            <th
                                class="whitespace-nowrap px-3 py-2 text-center font-semibold capitalize text-gray-600 dark:text-gray-200">
                                {{ $col }}
                            </th>
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
                            <td class="rp-td-resource">
                                {{ $resource }}
                            </td>
                            @foreach ($columns as $col)
                                <td class="rp-td-action">
                                    @if ($actions->contains($col))
                                        <x-filament::icon icon="heroicon-s-check-circle"
                                            class="mx-auto h-4 w-4 text-green-500 dark:text-green-400" />
                                    @else
                                        <x-filament::icon icon="heroicon-s-x-mark"
                                            class="mx-auto h-4 w-4 text-red-500 dark:text-red-400" />
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="rp-tfoot-row">
                        <td colspan="{{ count($columns) + 1 }}" class="px-3 py-1.5 text-gray-400 dark:text-gray-500">
                            {{ $groups->sum(fn($a) => $a->count()) }} permissions
                            across {{ $groups->count() }} {{ Str::plural('resource', $groups->count()) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

    @endif
</x-dynamic-component>
