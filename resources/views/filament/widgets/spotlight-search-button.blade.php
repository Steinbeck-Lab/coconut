<div class="ml-3">
    <button
        type="button"
        @click="$dispatch('spotlight-open')"
        class="group inline-flex items-center gap-2.5 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 shadow-sm hover:shadow whitespace-nowrap"
    >
        <svg class="w-4 h-4 flex-shrink-0 text-gray-500 dark:text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span class="flex-shrink-0">Search</span>
        <kbd class="flex items-center justify-center px-2 py-0.5 text-[11px] font-semibold rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm min-w-[32px] flex-shrink-0 ml-2">
            ⌘K
        </kbd>
    </button>
</div>

