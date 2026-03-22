export default function calculator() {
    return {
        open: false,
        display: '',
        expression: '',
        history: [],

        init() {
            window.addEventListener('keydown', (e) => {
                if (!this.open) { return; }

                if (/[0-9.,]/.test(e.key)) {
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
            if (!this.display) { return; }
            if (!/[+\-*/÷×−]/.test(this.display)) { return; }

            try {
                const expr = this.display;
                const jsExpr = expr.replace(/÷/g, '/').replace(/×/g, '*').replace(/−/g, '-');
                const result = Function('"use strict"; return (' + jsExpr + ')')();

                const formatted = Number.isFinite(result)
                    ? parseFloat(result.toFixed(10)).toString()
                    : 'Errore';

                this.history.unshift({ id: Date.now(), expr, result: formatted });
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
