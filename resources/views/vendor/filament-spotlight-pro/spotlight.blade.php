<div
    class="fi-spotlight-pro spotlight relative z-40"
    role="dialog"
    aria-modal="true"
    x-data="FilamentSpotlightPro('{{ $this->__id }}')"
    x-trap="isOpen"
    x-cloak
    wire:key="spotlight"
    @foreach($this->plugin->hotkeys as $hotkey)
        @keydown.{{ $hotkey }}.window.prevent="toggle()"
    @endforeach
>
    <!-- Backdrop -->
    <div
        class="spotlight__backdrop fixed inset-0 bg-gray-500/25 backdrop-blur-sm transition-opacity"
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100 pointer-events-auto"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0 pointer-events-none"
        :aria-hidden="! isOpen"
    ></div>

    <div
        class="spotlight__container fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20"
        x-bind="spotlight"
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <!-- Spotlight -->
        <div
            class="
                spotlight__panel
                mx-auto max-w-screen-md divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/5
                text-sm text-gray-700 dark:text-gray-300
                dark:divide-gray-500/20 dark:bg-gray-900 dark:ring-white/10
            "
            x-on:click.outside="handleEscape()"
        >
            @include('filament-spotlight-pro::partials.search')

            @if ($this->groupedResults !== null)
                @if ($this->groupedResults->isEmpty())
                    @include('filament-spotlight-pro::partials.empty')
                @else
                    @include('filament-spotlight-pro::partials.results')
                @endif
            @endif

            @include('filament-spotlight-pro::partials.footer')
        </div>
    </div>

    <x-filament-actions::modals/>
</div>
