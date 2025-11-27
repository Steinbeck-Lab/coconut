<div
    x-show="!!activeElementData"
    class="spotlight-footer flex items-center justify-between bg-gray-50 px-4 py-2.5 text-gray-700 dark:bg-gray-800 dark:text-gray-300"
>
    <div class="spotlight-footer__group flex items-center gap-2">
        <kbd aria-labelledby="esc-label">
            <span class="block h-3 leading-none">esc</span>
        </kbd>

        <div id="esc-label">
            <span x-show="hasContext()">
                {{ __('filament-spotlight-pro::spotlight.footer.back') }}
            </span>

            <span x-show="! hasContext()">
                {{ __('filament-spotlight-pro::spotlight.footer.close') }}
            </span>
        </div>
    </div>

    <div class="spotlight-footer__group flex items-center gap-2">
        <kbd aria-labelledby="enter-label">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="spotlight-footer__key-icon size-3 -scale-y-100">
              <path fill-rule="evenodd" d="M12.5 9.75A2.75 2.75 0 0 0 9.75 7H4.56l2.22 2.22a.75.75 0 1 1-1.06 1.06l-3.5-3.5a.75.75 0 0 1 0-1.06l3.5-3.5a.75.75 0 0 1 1.06 1.06L4.56 5.5h5.19a4.25 4.25 0 0 1 0 8.5h-1a.75.75 0 0 1 0-1.5h1a2.75 2.75 0 0 0 2.75-2.75Z" clip-rule="evenodd" />
            </svg>
        </kbd>

        <div id="enter-label">
            <span x-show="activeElementData.url">
                {{ __('filament-spotlight-pro::spotlight.footer.goto') }}
            </span>

            <span x-show="! activeElementData.url && activeElementData.pushesContext">
                {{ __('filament-spotlight-pro::spotlight.footer.submenu') }}
            </span>

            <span x-show="! activeElementData.url && ! activeElementData.pushesContext">
                {{ __('filament-spotlight-pro::spotlight.footer.command') }}
            </span>
        </div>
    </div>
</div>
