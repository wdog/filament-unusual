<div x-data="calculator()" x-on:toggle-calculator.window="open = !open"
    x-on:keydown.escape.window="open && (open = false)" x-cloak>
    {{-- Backdrop overlay --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-gray-950/50 dark:bg-gray-950/75" x-on:click="open = false" aria-hidden="true">
    </div>

    {{-- Slide-over panel --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full rtl:-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full rtl:-translate-x-full" class="fi-calc-panel" role="dialog"
        aria-modal="true" aria-label="Calcolatrice">
        {{-- Header --}}
        <div class="fi-calc-header">
            <div class="flex items-center gap-2">
                @svg('heroicon-o-calculator', 'h-5 w-5 text-gray-400 dark:text-gray-500')
                <span class="font-semibold text-sm text-gray-950 dark:text-white">Calcolatrice</span>
            </div>
            <button type="button" class="fi-calc-close-btn" x-on:click="open = false" title="Chiudi"
                aria-label="Chiudi calcolatrice">
                @svg('heroicon-s-x-mark', 'h-5 w-5 text-gray-400 dark:text-gray-500')
            </button>
        </div>

        {{-- History --}}
        <div class="fi-calc-history">
            <div class="fi-calc-history-toolbar">
                <span
                    class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Storico</span>
                <button type="button" class="fi-calc-history-clear" x-on:click="clearHistory()"
                    :disabled="history.length === 0">Pulisci storico</button>
            </div>
            <div class="fi-calc-history-list">
                <template x-if="history.length === 0">
                    <p class="text-xs text-gray-400 dark:text-gray-600 text-center py-6 select-none">Nessuna operazione
                    </p>
                </template>
                <template x-for="item in history" :key="item.id">
                    <button type="button" class="fi-calc-history-item" x-on:click="useHistory(item.result)">
                        <span class="text-gray-500 dark:text-gray-400 text-xs" x-text="item.expr"></span>
                        <span class="font-semibold text-gray-900 dark:text-white text-sm tabular-nums"
                            x-text="'= ' + item.result"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Display --}}
        <div class="fi-calc-display-wrap">
            <div class="fi-calc-display-expr" x-text="expression || '\u00a0'"></div>
            <input type="text" x-model="display" class="fi-calc-display" readonly placeholder="0"
                aria-label="Valore calcolatrice">
        </div>

        {{-- Keyboard --}}
        <div class="fi-calc-keyboard">
            {{-- Row 1: AC, ⌫, ÷ --}}
            <button type="button" tabindex="-1" class="fi-calc-btn fi-calc-btn-clear col-span-2"
                x-on:click="reset()">AC</button>
            <button type="button" tabindex="-1" class="fi-calc-btn fi-calc-btn-action" x-on:click="backspace()"
                aria-label="Cancella cifra">
                <svg class="h-4 w-4 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M7.22 3.22A.75.75 0 0 1 7.75 3h9.5a.75.75 0 0 1 .75.75v12.5a.75.75 0 0 1-.75.75h-9.5a.75.75 0 0 1-.53-.22l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5zm2.56 4.28a.75.75 0 1 0-1.06 1.06L9.94 10l-1.22 1.22a.75.75 0 1 0 1.06 1.06L11 11.06l1.22 1.22a.75.75 0 1 0 1.06-1.06L12.06 10l1.22-1.22a.75.75 0 0 0-1.06-1.06L11 8.94 9.78 7.72z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <button type="button" tabindex="-1" class="fi-calc-btn fi-calc-btn-operator"
                x-on:click="press('/')">÷</button>

            {{-- Row 2 --}}
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('7')">7</button>
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('8')">8</button>
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('9')">9</button>
            <button type="button" tabindex="-1" class="fi-calc-btn fi-calc-btn-operator"
                x-on:click="press('*')">×</button>

            {{-- Row 3 --}}
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('4')">4</button>
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('5')">5</button>
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('6')">6</button>
            <button type="button" tabindex="-1" class="fi-calc-btn fi-calc-btn-operator"
                x-on:click="press('-')">−</button>

            {{-- Row 4 --}}
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('1')">1</button>
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('2')">2</button>
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('3')">3</button>
            <button type="button" tabindex="-1" class="fi-calc-btn fi-calc-btn-operator"
                x-on:click="press('+')">+</button>

            {{-- Row 5 --}}
            <button type="button" tabindex="-1" class="fi-calc-btn col-span-2" x-on:click="press('0')">0</button>
            <button type="button" tabindex="-1" class="fi-calc-btn" x-on:click="press('.')">.</button>
            <button type="button" tabindex="-1" class="fi-calc-btn fi-calc-btn-equals"
                x-on:click="calculate()">=</button>
        </div>
    </div>
</div>

<script>
    if (typeof window.__fiCalcRegistered === 'undefined') {
        window.__fiCalcRegistered = true;

        function calculator() {
            return {
                open: false,
                display: '',
                expression: '',
                history: [],

                init() {
                    window.addEventListener('keydown', (e) => {
                        if (!this.open) {
                            return;
                        }

                        if (/[0-9.,]/.test(e.key)) {
                            if (e.key === '.' || e.key === ',') {
                                // bock if has one                                 const parts = this.display.split(/[\+\-\*\/]/);
                                const parts = this.display.split(/[\+\-\*\/]/);
                                const current = parts[parts.length - 1];

                                if (current.includes('.') || current.includes(',')) {
                                    return;
                                }
                                // not as first char
                                if (current === '') {
                                    return;
                                }
                            }
                            this.press(e.key);

                        } else if (e.key === '+' || e.key === '-') {
                            this.press(e.key);
                        } else if (e.key === '*') {
                            this.press('*');
                        } else if (e.key === '/') {
                            e.preventDefault();
                            this.press('/');
                        } else if (e.key === 'Enter' || e.key === '=') {
                            e.preventDefault();
                            this.calculate();
                        } else if (e.key === 'Backspace') {
                            this.backspace();
                        } else if (e.key === 'Delete') {
                            this.reset();
                        }
                    });
                },


                press(value) {
                    this.display += value === ',' ? '.' : value;
                },

                backspace() {
                    this.display = this.display.slice(0, -1);
                },

                reset() {
                    this.display = '';
                    this.expression = '';
                },

                calculate() {
                    if (!this.display) {
                        return;
                    }
                    if (!/[+\-*/÷×−]/.test(this.display)) {
                        return;
                    }

                    try {
                        const expr = this.display;
                        const jsExpr = expr.replace(/÷/g, '/').replace(/×/g, '*').replace(/−/g, '-');
                        const result = Function('"use strict"; return (' + jsExpr + ')')();

                        const formatted = Number.isFinite(result) ?
                            parseFloat(result.toFixed(5)).toString() :
                            'Errore';

                        this.history.unshift({
                            id: Date.now(),
                            expr,
                            result: formatted
                        });
                        this.expression = expr + ' =';
                        this.display = formatted;
                    } catch (e) {
                        this.display = 'Errore';
                        this.expression = '';
                    }
                },

                useHistory(value) {
                    this.display = value;
                    this.expression = '';
                },

                clearHistory() {
                    this.history = [];
                },
            };
        }
    }
</script>
