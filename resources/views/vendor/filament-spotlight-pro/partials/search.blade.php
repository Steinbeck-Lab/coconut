<div class="spotlight-search h-12 flex items-center gap-4 px-4">
    <div class="spotlight-search__icon-container text-gray-400 dark:text-gray-500">
        <svg class="spotlight-search__icon animate-spin size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading>
          <!-- Background ring -->
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"></circle>
          <!-- Spinning one-third arc -->
          <path class="opacity-80" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 2a10 10 0 0 1 10 10"></path>
        </svg>

        <div wire:loading.remove>
            @if ($this->context->hasItems())
                <svg class="spotlight-search__icon size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75 14.25 12m0 0 2.25 2.25M14.25 12l2.25-2.25M14.25 12 12 14.25m-2.58 4.92-6.374-6.375a1.125 1.125 0 0 1 0-1.59L9.42 4.83c.21-.211.497-.33.795-.33H19.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-9.284c-.298 0-.585-.119-.795-.33Z" />
                </svg>
            @else
                <svg class="spotlight-search__icon size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            @endif
        </div>
    </div>

    <div class="spotlight-search__content flex-1 flex gap-2 items-center text-gray-900 dark:text-gray-100">
        @if ($this->context->hasItems())
            @foreach ($this->context->stack as $item)
                <span class="spotlight-search__path-item whitespace-nowrap">{{ $item['label'] }}</span>
                <span>/</span>
            @endforeach
        @endif
        <input
            x-ref="search"
            x-model.debounce.250ms="search"
            type="text"
            class="spotlight-search__input flex-1 !p-0 !border-0 outline-0 bg-transparent text-gray-700 dark:text-gray-300 text-sm placeholder:text-gray-400 dark:placeholder:text-gray-500 ring-0"
            placeholder="{{ __('filament-spotlight-pro::spotlight.search.placeholder') }}"
            role="combobox"
            aria-expanded="false"
            aria-controls="options"
        >
    </div>
</div>
