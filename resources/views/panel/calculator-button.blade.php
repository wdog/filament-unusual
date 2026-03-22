<div x-data>
    <button
        type="button"
        x-on:click="$dispatch('toggle-calculator')"
        class="flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-gray-200 transition-colors"
        title="Calcolatrice"
    >
        @svg('heroicon-c-calculator', 'w-5 h-5')
    </button>

    @include('filament-unusual::components.calculator.calculator-drawer')
</div>
