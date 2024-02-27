<div>
    <div class="my-10 w-full mx-auto px-12 max-w-4xl lg:max-w-7xl">
        <div class="mb-4 border-gray-200 py-5 sm:flex sm:items-center sm:justify-between">
            <h2 class="text-2xl pb-0 font-bold tracking-tight text-white sm:text-2xl"><span
                class="-mb-1 block text-primary-dark bg-clip-text">Data Sources</span></h2>
        </div>
        @foreach ($collections as $collection)
        <span>
            <a target="_blank" href="search?type=tags&amp;query={{ $collection }}&amp;tagType=dataSource">
            <span class="mr-1 mb-1 inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">{{ $collection }}
            </span>
            </a>
        </span>
        @endforeach
    </div>
</div>
