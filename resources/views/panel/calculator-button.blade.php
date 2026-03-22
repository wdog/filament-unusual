<div x-data x-init="window.addEventListener('keydown', (e) => {
    const keys = '{{ $shortcut }}'.toLowerCase().split('+');


    if (
        (keys.includes('ctrl') ? e.ctrlKey : true) &&
        (keys.includes('shift') ? e.shiftKey : true) &&
        (keys.includes('alt') ? e.altKey : true) &&
        (keys.includes('super') ? e.metaKey : true) &&
        e.key.toLowerCase() === keys[keys.length - 1]
    ) {
        console.log('pressed');
        $dispatch('toggle-calculator');
    }
});">
    <button type="button" id="{{ $buttonId }}" x-on:click="$dispatch('toggle-calculator')"
        class="flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-gray-200 transition-colors"
        title="Calcolatrice">
        @svg('heroicon-c-calculator', 'w-5 h-5')
    </button>

    @include('filament-unusual::components.calculator.calculator-drawer')
</div>
