<div>
    <div class="mt-24">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-primary-dark">Browse collections</h1>
            <p class="mt-4 max-w-xl text-sm text-gray-700">Explore our database of natural products to uncover their
                unique properties. Search, filter, and discover the diverse realm of chemistry.
            </p>
        </div>
    </div>
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
        <div class="bg-white">
            <div class="px-4 mx-auto max-w-7xl">
                <div class="flex h-16 flex-shrink-0 rounded-md border border-zinc-900/5 border-b-4">
                    <div class="flex flex-1 justify-between px-4 md:px-0">
                        <div class="flex flex-1">
                            <div class="flex w-full md:ml-0"><label for="search-field" class="sr-only">Search</label>
                                <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                                    <div class="px-2 pointer-events-none absolute inset-y-0 left-0 flex items-center">
                                        <svg class="h-5 w-5 flex-shrink-0" x-description="Heroicon name: mini/magnifying-glass" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>

                                    <input name="query" id="query"
                                        class="h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-900 placeholder-gray-500 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:block"
                                        wire:model.live="query" placeholder="Search collections" type="search"
                                        autofocus="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-8 lg:max-w-7xl lg:px-8">
        <div class="p-4 w-full">
            {{ $collections->links() }}
        </div>
        <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
            @foreach ($collections as $collection)
            <a href="search?type=tags&amp;q={{ $collection->title }}&amp;tagType=dataSource" class="border relative flex h-80 w-56 flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 xl:w-auto">
                @if($collection['image'] && $collection['image'] != '')
                    <span aria-hidden="true" class="absolute inset-0">
                    <img src="https://s3.uni-jena.de/coconut/{{ $collection->image }}" alt="" class="h-full w-full object-cover object-center">
                  </span>
                @endif
                <span aria-hidden="true" class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-gray-800 opacity-100"></span>
                <span class="relative mt-auto text-left text-xl font-bold text-white">{{ $collection->title }}</span>
                <span class="relative mt-1 text-left text-sm text-white ">{{ Str::limit($collection->description, 80, '...') }}</span>
            </a>
            @endforeach
        </div>
        <div class="p-4">
            {{ $collections->links() }}
        </div>
    </div>
</div>