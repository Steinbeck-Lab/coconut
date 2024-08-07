<div>
    <div class="mt-24">

        @if ($tagType == 'dataSource' && $collection)
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <p class="mt-4 max-w-7xl text-sm text-gray-700">#COLLECTION</p>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ $collection->title }}</h1>
                <p class="mt-4 max-w-7xl text-sm text-gray-700">{{ $collection->description }}</p>
                @if ($collection->license)
                    <p class="mt-4 max-w-7xl text-sm text-gray-700">License: {{ $collection->license->title }}</p>
                @endif
            </div>
        @elseif ($tagType == 'organisms' && $organisms)
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <p class="mt-4 max-w-xl text-sm text-gray-700">#ORGANISMS</p>
                @foreach ($organisms as $organism)
                    <span class="text-3xl font-bold text-gray-900"><span
                            class="italic">{{ ucfirst($organism) }}</span></span>
                    @if (!$loop->last)
                        ,
                    @endif
                @endforeach
            </div>
        @else
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">Browse compounds</h1>
                <p class="mt-4 max-w-xl text-sm text-gray-700">Explore our database of natural products to uncover their
                    unique properties. Search, filter, and discover the diverse realm of chemistry.
                </p>
            </div>
        @endif
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
                                        <svg class="h-5 w-5 flex-shrink-0"
                                            x-description="Heroicon name: mini/magnifying-glass"
                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                            aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </div>

                                    <input name="query" id="query"
                                        class="h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-900 placeholder-gray-500 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:block"
                                        wire:model.live="query"
                                        placeholder="Search compound name, SMILES, InChI, InChI Key" type="search"
                                        autofocus="">
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center md:ml-6">
                            <div><button type="button" onclick="Livewire.dispatch('openModal', { smiles: query })"
                                    class="rounded-md text-gray-900 bg-white mr-3 py-3 px-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-3 ml-2 h-6 w-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                                    </svg>
                                </button></div>
                            {{-- <div><button type="button"
                                    class="rounded-md bg-white text-gray-900 mr-3 py-3 px-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2"><svg
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                        class="mr-3 ml-2 h-6 w-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5">
                                        </path>
                                    </svg></button></div> --}}
                            <div>
                                {{-- <div><button id="headlessui-menu-button-2" type="button" aria-haspopup="menu"
                                    aria-expanded="false"
                                    class="rounded-md bg-white text-gray-900 mr-3 py-3 px-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2"><svg
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-3 ml-2 h-6 w-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3">
                                        </path>
                                    </svg></button></div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <livewire:structure-editor />

    @if ($molecules && count($molecules) > 0)
        <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-8 lg:max-w-7xl lg:px-8">
            <div class="p-4 w-full">
                {{ $molecules->links() }}
            </div>
            <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
                @foreach ($molecules as $molecule)
                    <livewire:molecule-card :key="$molecule->id" :molecule="json_encode($molecule)" />
                @endforeach
            </div>
            <div class="p-4">
                {{ $molecules->links() }}
            </div>
        </div>
    @else
        <div class="text-center pt-10 mt-10">
            <svg class="w-12 h-12 mx-auto" viewBox="0 0 78 78" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_1_2)">
                    <path d="M70.1638 11.819L66.3621 23.4827" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M68.0431 32.4052L74.8966 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M68.0431 51.2586L74.8966 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M65.8448 49.6293L71.5086 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M61.0603 54.3621L48.6983 50.3793" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 50.3793V33.2845" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M45.9828 48.8017V34.8621" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M61.0603 29.3017L48.6983 33.2845" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 33.2845L33.9052 24.75" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M32.5345 25.5259V12.4397" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M35.2759 25.5259V12.4397" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M33.9052 24.75L22.7845 31.1638" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 37.9397V50.3793" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M20.4828 51.1552L9.10345 57.7241" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 48.8017L7.73276 55.3707" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 50.3793L30.2069 56.7672" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 50.3793L37.6034 56.7672" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M33.9052 63.569V75.9828" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M15.4138 31.1638L4.31897 24.75" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M62.5345 24.9569H63.6466L66.3879 30.1552V24.9569H67.2155V31.1638H66.0776L63.3362 25.9914V31.1638H62.5345V24.9569Z"
                        fill="black" />
                    <path
                        d="M62.5345 52.5H63.6466L66.3879 57.6983V52.5H67.2155V58.7069H66.0776L63.3362 53.5345V58.7069H62.5345V52.5Z"
                        fill="black" />
                    <path
                        d="M33.9052 5.12069C33.2845 5.12069 32.7931 5.34482 32.431 5.7931C32.069 6.24138 31.8879 6.86207 31.8879 7.65517C31.8879 8.43103 32.069 9.05172 32.431 9.51724C32.7931 9.96551 33.2845 10.1897 33.9052 10.1897C34.5086 10.1897 34.9914 9.96551 35.3535 9.51724C35.7155 9.06896 35.8966 8.44827 35.8966 7.65517C35.8966 6.87931 35.7155 6.25862 35.3535 5.7931C34.9914 5.34482 34.5086 5.12069 33.9052 5.12069ZM33.9052 4.44827C34.7672 4.44827 35.4569 4.74138 35.9741 5.32758C36.4914 5.91379 36.75 6.69827 36.75 7.68103C36.75 8.66379 36.4914 9.44827 35.9741 10.0345C35.4569 10.6207 34.7672 10.9138 33.9052 10.9138C33.0259 10.9138 32.3276 10.6207 31.8103 10.0345C31.2931 9.44827 31.0345 8.66379 31.0345 7.68103C31.0345 6.69827 31.2931 5.91379 31.8103 5.32758C32.3276 4.74138 33.0259 4.44827 33.9052 4.44827Z"
                        fill="black" />
                    <path
                        d="M16.7586 30.181H17.8707L20.6121 35.3793V30.181H21.4397V36.3879H20.3017L17.5603 31.2155V36.3879H16.7586V30.181Z"
                        fill="black" />
                    <path
                        d="M4.31897 56.3793C3.69828 56.3793 3.2069 56.6034 2.84483 57.0517C2.48276 57.5 2.30172 58.1207 2.30172 58.9138C2.30172 59.6897 2.48276 60.3103 2.84483 60.7759C3.2069 61.2241 3.69828 61.4483 4.31897 61.4483C4.92241 61.4483 5.40517 61.2241 5.76724 60.7759C6.12931 60.3276 6.31035 59.7069 6.31035 58.9138C6.31035 58.1379 6.12931 57.5172 5.76724 57.0517C5.40517 56.6034 4.92241 56.3793 4.31897 56.3793ZM4.31897 55.681C5.18103 55.681 5.87069 55.9741 6.38793 56.5603C6.90517 57.1465 7.16379 57.931 7.16379 58.9138C7.16379 59.8965 6.90517 60.681 6.38793 61.2672C5.87069 61.8534 5.18103 62.1465 4.31897 62.1465C3.43966 62.1465 2.74138 61.8534 2.22414 61.2672C1.7069 60.681 1.44828 59.8965 1.44828 58.9138C1.44828 57.931 1.7069 57.1465 2.22414 56.5603C2.74138 55.9741 3.43966 55.681 4.31897 55.681Z"
                        fill="black" />
                    <path
                        d="M31.5517 55.8103H32.6638L35.4052 61.0086V55.8103H36.2328V62.0172H35.0948L32.3535 56.8448V62.0172H31.5517V55.8103Z"
                        fill="black" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M61.194 0.892153L70.1146 9.91002C70.6816 10.4859 71 11.2659 71 12.0792C71 12.8924 70.6816 13.6724 70.1146 14.2483L65.1786 19.2692L51.9689 5.89107L56.932 0.892153C57.502 0.320612 58.2717 0 59.0739 0C59.876 0 60.6457 0.320612 61.2157 0.892153H61.194ZM43.5211 41.1808L32.7957 44.5391C25.2666 46.1907 25.3319 47.9249 26.729 40.7348L30.3385 27.8301L49.4682 8.42906L62.678 21.8072L43.532 41.1973L43.5211 41.1808ZM32.5891 30.1149L41.2869 38.9235L34.2199 41.1257C28.7023 42.8489 28.7295 43.9995 30.2298 38.6207L32.5891 30.1424V30.1149Z"
                        fill="black" />
                </g>
                <defs>
                    <clipPath id="clip0_1_2">
                        <rect width="77.5862" height="78" fill="white" />
                    </clipPath>
                </defs>
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No results</h3>
            <p class="mt-1 text-sm text-gray-500">Please contact us below to report any issues.</p>
            <div class="mt-6">
                <a target="_blank" href="https://cheminf.uni-jena.de/"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Contact
                </a>
            </div>
        </div>
    @endif

    {{-- 
</div> --}}
