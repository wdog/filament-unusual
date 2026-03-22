export default function datePickerTableColumn({ name, recordKey, state }) {
    return {
        error: undefined,

        isEditing: false,

        isLoading: false,

        state,

        editingState: state,

        unsubscribeLivewireHook: null,

        init() {
            this.unsubscribeLivewireHook = Livewire.interceptMessage(
                ({ message, onSuccess }) => {
                    onSuccess(() => {
                        this.$nextTick(() => {
                            if (this.isLoading) {
                                return
                            }

                            if (
                                message.component.id !==
                                this.$root.closest('[wire\\:id]')?.attributes[
                                    'wire:id'
                                ].value
                            ) {
                                return
                            }

                            const serverState = this.getServerState()

                            if (
                                serverState === undefined ||
                                this.getNormalizedState() === serverState
                            ) {
                                return
                            }

                            this.state = serverState
                            this.editingState = serverState
                        })
                    })
                },
            )
        },

        startEditing() {
            this.editingState = this.state
            this.isEditing = true
            this.$nextTick(() => {
                const input = this.$refs.dateInput
                if (!input) return
                input.focus()
                input.showPicker?.()
            })
        },

        cancelEditing() {
            this.editingState = this.state
            this.error = undefined
            this.isEditing = false
        },

        async save() {
            const serverState = this.getServerState()

            if (this.getNormalizedEditingState() === serverState) {
                this.isEditing = false
                return
            }

            this.isLoading = true

            const response = await this.$wire.updateTableColumnState(
                name,
                recordKey,
                this.editingState,
            )

            this.error = response?.error ?? undefined

            if (!this.error) {
                this.state = this.editingState
                if (this.$refs.serverState) {
                    this.$refs.serverState.value = this.getNormalizedEditingState()
                }
                this.isEditing = false
            }

            this.isLoading = false
        },

        getServerState() {
            if (!this.$refs.serverState) {
                return undefined
            }

            return [null, undefined].includes(this.$refs.serverState.value)
                ? ''
                : this.$refs.serverState.value.replaceAll(
                      '\\' + String.fromCharCode(34),
                      String.fromCharCode(34),
                  )
        },

        getNormalizedState() {
            const state = Alpine.raw(this.state)
            return [null, undefined].includes(state) ? '' : state
        },

        getNormalizedEditingState() {
            const state = Alpine.raw(this.editingState)
            return [null, undefined].includes(state) ? '' : state
        },

        destroy() {
            this.unsubscribeLivewireHook?.()
        },
    }
}
