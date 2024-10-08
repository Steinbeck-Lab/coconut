<div>
    <div class="my-10 w-full mx-auto px-8 max-w-4xl lg:max-w-7xl">
        {{--
        <div>
            <div class="mb-4 border-gray-200 py-5 sm:flex sm:items-center sm:justify-between">
                <h2 class="text-2xl pb-0 font-bold tracking-tight text-white sm:text-2xl"><span
                    class="-mb-1 block text-primary-dark bg-clip-text">Data Sources</span></h2>
            </div>
            
        </div> 
        --}}
        <div class="bg-white">
            <div class="py-8 md:py-16 xl:mx-auto xl:max-w-7xl">
                <div class="px-4 sm:flex sm:items-center sm:justify-between sm:px-6 lg:px-8 xl:px-0">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Collections</h2>
                    <a href="/collections" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 sm:block">
                        Browse all collections
                        <span aria-hidden="true"> &rarr;</span>
                    </a>
                </div>
                <div class="mt-4 flow-root">
                    <div class="relative box-content py-2">
                        <div class="md:space-x-8 px-4 sm:px-6 lg:px-8 xl:relative xl:grid xl:grid-cols-5 xl:gap-x-8 xl:space-x-0 xl:px-0">
                            @foreach ($collections as $collection)
                                <a href="search?type=tags&amp;q={{ $collection['title'] }}&amp;tagType=dataSource" class="border relative flex h-80 w-full flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 xl:w-auto mb-10">
                                    @if($collection['image'] && $collection['image'] != '')
                                        <span aria-hidden="true" class="absolute inset-0">
                                        <img src="https://s3.uni-jena.de/coconut/{{ $collection['image'] }}" alt="" class="h-full w-full object-cover object-center">
                                    </span>
                                    @endif
                                    <span aria-hidden="true" class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-gray-800 opacity-100"></span>
                                    <span class="relative mt-auto text-left text-xl font-bold text-white">{{ $collection['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>