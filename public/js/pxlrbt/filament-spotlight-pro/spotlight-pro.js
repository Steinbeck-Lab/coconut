document.addEventListener('alpine:init', function () {
    Alpine.data('FilamentSpotlightPro', function (componentId) {
        return {
            isOpen: window.Livewire.find(componentId).$entangle('isOpen', false).live,
            search: window.Livewire.find(componentId).$entangle('search').live,
            context: window.Livewire.find(componentId).$entangle('context').live,

            allowMouse: true,
            activeItem: 0,
            activeElementData: {},

            spotlight: {
                '@spotlight-toggle.window': function () {
                    this.toggle()
                },

                '@spotlight-open.window': function () {
                    if (this.isOpen) {
                        return
                    }

                    this.open()
                },

                '@spotlight-close.window': function () {
                    if (! this.isOpen) {
                        return
                    }

                    this.close()
                },

                '@keydown.backspace.window': function () {
                    if (! this.isOpen) {
                        return
                    }

                    this.handleBackspace()
                },

                '@keydown.escape.window': function () {
                    if (! this.isOpen) {
                        return
                    }

                    this.handleEscape()
                },

                '@keydown.enter.window': function () {
                    if (! this.isOpen) {
                        return
                    }

                    this.select()
                },

                '@keydown.arrow-up.window': function (event) {
                    if (! this.isOpen) {
                        return
                    }

                    event.preventDefault()
                    this.previousItem()
                },

                '@keydown.arrow-down.window': function (event) {
                    if (! this.isOpen) {
                        return
                    }

                    event.preventDefault()
                    this.nextItem()
                },
            },

            init() {
                this.$watch('isOpen', () => {
                    this.focus()
                })

                this.$watch('search', () => {
                    this.activeItem = 0
                })

                this.$watch('context', () => {
                    this.activeItem = 0
                    this.search = ''
                    this.focus()
                })

                this.$watch('activeItem', () => {
                    this.scrollToActive()
                    this.updateActiveElementData()
                })

                Livewire.hook('morph.updated', () => {
                    this.$nextTick(() => this.updateActiveElementData())
                })
            },

            hasContext() {
                return Object.keys(this.context).length > 0
            },

            hasOpenFilamentActions() {
                return this.$wire.mountedActions.length > 0;
            },

            handleMouseEnter(index) {
                if (! this.allowMouse) {
                    return
                }

                this.activeItem = index
            },

            handleBackspace() {
                if (this.hasOpenFilamentActions()) {
                    return;
                }

                if (this.search.length === 0) {
                    this.$wire.popContext()
                }
            },

            handleEscape() {
                if (this.hasOpenFilamentActions()) {
                    return;
                }

                if (!this.isOpen) {
                    return;
                }

                if (Object.keys(this.context).length > 0) {
                    this.$wire.popContext()
                    return;
                }

                this.close()
            },

            toggle() {
                this.isOpen ? this.close() : this.open()
            },

            open() {
                this.isOpen = true;
            },

            close() {
                this.$wire.resetContext()

                this.isOpen = false;
                this.search = '';
            },

            focus() {
                this.$nextTick(() => {
                    this.$refs.search.focus()
                })
            },

            scrollToActive() {
                const el = this.getActiveElement()

                if (el) {
                    el.scrollIntoView({
                        block: 'nearest'
                    })
                }
            },

            debounceMouseInput() {
                this.allowMouse = false
                setTimeout(() => this.allowMouse = true, 50)
            },

            previousItem() {
                this.activeItem--

                this.debounceMouseInput()

                if (this.activeItem < 0) {
                    this.activeItem = document.querySelectorAll('.spotlight-result').length - 1
                }
            },

            nextItem() {
                this.activeItem++

                this.debounceMouseInput()

                if (this.activeItem >= document.querySelectorAll('.spotlight-result').length) {
                    this.activeItem = 0
                }
            },

            getActiveElement() {
                return document.getElementById('option-' + this.activeItem)
            },

            updateActiveElementData() {
                let el = this.getActiveElement()
                if (el) {
                    this.activeElementData = Alpine.$data(el) ?? null
                }
            },

            select() {
                const el = document.querySelector('#option-' + this.activeItem)

                if (el) {
                    el.click()

                    if (this.activeElementData?.closeOnSelect) {
                        this.close()
                    }
                }
            }
        }
    })
})
