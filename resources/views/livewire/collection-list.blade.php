<div>
    <div class="mt-24">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-primary-dark">Browse collections</h1>
            <p class="mt-4 max-w-xl text-sm text-gray-700">Explore our database of natural products (NPs) to uncover their
                unique properties. Search, sort, and discover the diverse realm of NP chemistry.
            </p>
        </div>
    </div>
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
        <div class="bg-white">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-3 rounded-md border border-gray-900 border-b-4 px-4 py-3 sm:flex-row sm:items-center sm:gap-3 md:py-2">
                    <div class="flex min-w-0 flex-1">
                        <label for="search-field" class="sr-only">Search</label>
                        <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-2">
                                <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input name="query" id="query"
                                class="h-10 w-full rounded-md border border-gray-300 py-2 pl-8 pr-3 text-sm text-gray-900 placeholder-gray-500 focus:border-gray-400 focus:outline-none focus:ring-0 sm:border-transparent"
                                wire:model.live="query" placeholder="Search collections" type="search"
                                autofocus="">
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <label for="sortBy" class="text-sm font-medium text-gray-600 whitespace-nowrap">Sort by:</label>
                        <div class="flex min-w-0 items-stretch">
                            <select id="sortBy" wire:model.live="sortBy"
                                class="min-w-0 flex-1 rounded-l-md rounded-r-none border border-gray-300 border-r-0 bg-white py-2 pl-3 pr-8 text-sm text-gray-700 focus:border-gray-400 focus:outline-none focus:ring-0 sm:min-w-[10rem]">
                                @foreach ($sortByOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="button"
                                wire:click="toggleSortDir"
                                class="inline-flex shrink-0 items-center justify-center rounded-r-md border border-gray-300 bg-white px-2.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 focus:border-gray-400 focus:outline-none focus:ring-0"
                                aria-label="{{ $sortDir === 'desc' ? 'Sort descending (click for ascending)' : 'Sort ascending (click for descending)' }}"
                                title="{{ $sortDir === 'desc' ? 'Descending' : 'Ascending' }}">
                                @if ($sortDir === 'desc')
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M14.78 11.78a.75.75 0 01-1.06 0L10 8.06 6.28 11.78a.75.75 0 01-1.06-1.06l4.25-4.25a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-8 lg:max-w-7xl lg:px-8">
        @if ($collections->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                </svg>
                @if ($query !== '')
                    <h3 class="mt-4 text-sm font-semibold text-gray-900">No collections match your search</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        No published collections found for &ldquo;{{ $query }}&rdquo;. Try different keywords or clear your search.
                    </p>
                    <div class="mt-6">
                        <button type="button"
                            wire:click="clearSearch"
                            class="inline-flex items-center rounded-md bg-primary-dark px-3 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-dark">
                            Clear search
                        </button>
                    </div>
                @else
                    <h3 class="mt-4 text-sm font-semibold text-gray-900">No collections to show</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        There are no published collections available right now. Please check back later.
                    </p>
                @endif
            </div>
        @else
            <div class="pb-4 w-full">
                {{ $collections->links() }}
            </div>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($collections as $collection)
                <a href="search?type=tags&amp;q={{ $collection->title }}&amp;tagType=dataSource" class="group relative flex h-80 flex-col overflow-hidden rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-lg hover:border-gray-300 transition-all duration-200">
                    @if($collection['image'] && $collection['image'] != '')
                        <span aria-hidden="true" class="absolute inset-0">
                            <img src="{{ public_storage_url($collection->image) }}" alt="" class="h-full w-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                        </span>
                    @endif
                    <span aria-hidden="true" class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-gray-900/90 to-transparent"></span>
                    <span class="relative mt-auto text-left text-xl font-bold text-white drop-shadow-sm">{{ $collection->title }}</span>
                    <span class="relative mt-1 text-left text-sm text-white/90">{{ Str::limit($collection->description, 80, '...') }}</span>
                </a>
                @endforeach
            </div>
            <div class="pt-6">
                {{ $collections->links() }}
            </div>
        @endif
    </div>
</div>
