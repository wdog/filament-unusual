/**
 * Alpine component for DateIntervalPicker.
 *
 * State sync strategy:
 *   A hidden <input wire:model x-ref="wireInput"> holds the Y-m-d value that
 *   Livewire reads on form submission.  When prev/next are clicked, `isoValue`
 *   is updated and we write + dispatch an 'input' event on the hidden input so
 *   Livewire's deferred wire:model queues the new value for the next request.
 *
 *   We intentionally do NOT use $wire.$entangle() because reassigning the
 *   returned proxy object from inside a constructor function breaks entanglement.
 */
export default function dateIntervalPicker({ initialState, locale, step, displayFormat }) {
    return {
        isoValue: initialState ?? '',

        locale,

        step,

        displayFormat,

        displayText: '',

        init() {
            this.updateDisplay()
        },

        // ── Display ────────────────────────────────────────────────────────────

        updateDisplay() {
            if (!this.isoValue) {
                this.displayText = ''
                return
            }

            const d = this.parseIso(this.isoValue)

            if (!d) {
                this.displayText = ''
                return
            }

            this.displayText = this.formatDate(d)
        },

        /**
         * Format a Date using Carbon-compatible isoFormat tokens.
         * Supported: dddd ddd DD D MMMM MMM MM M YYYY YY
         * The sentinel '__WEEK__' triggers week-range formatting instead.
         */
        formatDate(d) {
            if (this.displayFormat === '__WEEK__') {
                return this.formatWeekRange(d)
            }

            const locale = this.locale
            const fmt    = this.displayFormat
            const parts  = {
                YYYY: d.getFullYear(),
                YY:   String(d.getFullYear()).slice(-2),
                MMMM: d.toLocaleDateString(locale, { month: 'long' }),
                MMM:  d.toLocaleDateString(locale, { month: 'short' }),
                MM:   String(d.getMonth() + 1).padStart(2, '0'),
                M:    String(d.getMonth() + 1),
                DD:   String(d.getDate()).padStart(2, '0'),
                D:    String(d.getDate()),
                dddd: d.toLocaleDateString(locale, { weekday: 'long' }),
                ddd:  d.toLocaleDateString(locale, { weekday: 'short' }),
            }

            // Longest tokens first to avoid partial matches (MMMM before MMM, etc.)
            return ['dddd', 'ddd', 'YYYY', 'MMMM', 'MMM', 'MM', 'DD', 'YY', 'M', 'D']
                .reduce((str, token) => str.replaceAll(token, parts[token]), fmt)
        },

        /**
         * Format a week range, e.g.:
         *   "12 - 18 lug 2024"          (same month)
         *   "28 lug - 3 ago 2024"       (different months, same year)
         *   "30 dic 2024 - 5 gen 2025"  (different years)
         */
        formatWeekRange(d) {
            const locale = this.locale

            // Find Monday of the week (ISO: week starts Monday)
            const from = new Date(d)
            const day  = d.getDay()
            from.setDate(d.getDate() - (day === 0 ? 6 : day - 1))

            const to = new Date(from)
            to.setDate(from.getDate() + 6)

            const fmtDay = (dt) => dt.getDate()
            const fmtMon = (dt) => dt.toLocaleDateString(locale, { month: 'short' })
            const fmtYr  = (dt) => dt.getFullYear()

            if (fmtYr(from) !== fmtYr(to)) {
                return `${fmtDay(from)} ${fmtMon(from)} ${fmtYr(from)} - ${fmtDay(to)} ${fmtMon(to)} ${fmtYr(to)}`
            }

            if (fmtMon(from) !== fmtMon(to)) {
                return `${fmtDay(from)} ${fmtMon(from)} - ${fmtDay(to)} ${fmtMon(to)} ${fmtYr(from)}`
            }

            return `${fmtDay(from)} - ${fmtDay(to)} ${fmtMon(from)} ${fmtYr(from)}`
        },

        // ── Navigation ─────────────────────────────────────────────────────────

        prev() {
            this.shiftDate(-1)
        },

        next() {
            this.shiftDate(+1)
        },

        shiftDate(delta) {
            const d = this.isoValue ? this.parseIso(this.isoValue) : new Date()

            if (!d) return

            if (this.step === 'year') {
                d.setFullYear(d.getFullYear() + delta)
            } else if (this.step === 'month') {
                d.setMonth(d.getMonth() + delta)
            } else if (this.step === 'week') {
                d.setDate(d.getDate() + delta * 7)
            } else {
                d.setDate(d.getDate() + delta)
            }

            this.isoValue = this.toIso(d)
            this.updateDisplay()
            this.syncWire()
        },

        // ── Livewire sync ──────────────────────────────────────────────────────

        /**
         * Write isoValue into the hidden wire:model input and fire an 'input'
         * event so Livewire's deferred binding queues the update.
         */
        syncWire() {
            const el = this.$refs.wireInput
            if (!el) return
            el.value = this.isoValue
            el.dispatchEvent(new Event('input'))
        },

        // ── Helpers ────────────────────────────────────────────────────────────

        /**
         * Parse a date string (Y-m-d, Y-m, or Y) at local noon to avoid DST issues.
         * Partial formats are padded to a full date before parsing.
         */
        parseIso(iso) {
            if (!iso) return null
            const parts = iso.split('-')
            const full  = [
                parts[0] ?? '2000',
                (parts[1] ?? '01').padStart(2, '0'),
                (parts[2] ?? '01').padStart(2, '0'),
            ].join('-')
            const d = new Date(full + 'T12:00:00')
            return isNaN(d.getTime()) ? null : d
        },

        /**
         * Serialize a Date to the storage format based on step:
         *   day / week → YYYY-MM-DD
         *   month      → YYYY-MM
         *   year       → YYYY
         */
        toIso(date) {
            const y = String(date.getFullYear())
            const m = String(date.getMonth() + 1).padStart(2, '0')
            const d = String(date.getDate()).padStart(2, '0')

            if (this.step === 'year')  return y
            if (this.step === 'month') return `${y}-${m}`
            if (this.step === 'week') {
                // Store as the Monday (ISO week start) of the selected date
                const monday = new Date(date)
                const dow    = date.getDay()
                monday.setDate(date.getDate() - (dow === 0 ? 6 : dow - 1))
                const my = String(monday.getFullYear())
                const mm = String(monday.getMonth() + 1).padStart(2, '0')
                const md = String(monday.getDate()).padStart(2, '0')
                return `${my}-${mm}-${md}`
            }
            return `${y}-${m}-${d}`
        },
    }
}
