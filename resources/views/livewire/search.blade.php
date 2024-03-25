<div>
    <div class="mt-24">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Browse compounds</h1>
            <p class="mt-4 max-w-xl text-sm text-gray-700">Our thoughtfully designed workspace objects are crafted in
                limited runs. Improve your productivity and organization with these sale items before we run out.</p>
        </div>
    </div>
    <div class="bg-white">
        <div class="px-8 pt-5">
            <div class="flex h-16 flex-shrink-0 bg-white">
                <div class="flex flex-1 justify-between px-4 md:px-0">
                    <div class="flex border-b border-zinc-900/5 border-b-2 flex-1">
                        <form class="flex w-full md:ml-0" action="#" method="GET"><label for="mobile-search-field"
                                class="sr-only">Search</label><label for="desktop-search-field"
                                class="sr-only">Search</label>
                            <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center"><svg
                                        class="h-5 w-5 flex-shrink-0"
                                        x-description="Heroicon name: mini/magnifying-glass"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                        aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                            clip-rule="evenodd"></path>
                                    </svg></div><input name="mobile-search-field" id="mobile-search-field"
                                    class="h-full w-full border-transparent py-2 pl-8 pr-3 text-base text-gray-900 placeholder-gray-500 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:hidden"
                                    placeholder="Search" type="search" autofocus=""><input
                                    name="desktop-search-field" id="desktop-search-field"
                                    class="hidden h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-900 placeholder-gray-500 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:block"
                                    placeholder="Search compound name, SMILES, InChI, InChI Key" type="search"
                                    autofocus="">
                            </div>
                        </form>
                    </div>
                    <div class="flex items-center md:ml-6">
                        <div><button type="button"
                                class="rounded-full bg-white p-1 mr-3 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                    class="mr-3 ml-2 h-6 w-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6">
                                    </path>
                                </svg></button><!----></div>
                        <div><button type="button"
                                class="rounded-full bg-white p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                    class="mr-3 ml-2 h-6 w-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5">
                                    </path>
                                </svg></button><!----></div>
                        <div data-headlessui-state="" class="ml-3 mb-1 relative">
                            <div><button id="headlessui-menu-button-2" type="button" aria-haspopup="menu"
                                    aria-expanded="false" data-headlessui-state=""
                                    class="rounded-full bg-white p-1 mr-3 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2"><svg
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-3 ml-2 h-6 w-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3">
                                        </path>
                                    </svg></button></div><!---->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <livewire:molecule-editor />
    </div>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-8 lg:max-w-7xl lg:px-8">
        <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
            @foreach ($molecules as $molecule)
                <div class="rounded-lg hover:shadow-lg shadow border">
                    <livewire:molecule-card :molecule="json_encode($molecule)" />
                </div>
            @endforeach
        </div>
    </div>
</div>
