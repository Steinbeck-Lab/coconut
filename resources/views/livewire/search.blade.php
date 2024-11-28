<div x-data="{ activeTab: @entangle('activeTab'), view: 'card' }">
    <div class="mt-24 px-4 lg:px-8">
        @if ($tagType == 'dataSource' && $collection)
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <p class="mt-4 max-w-7xl text-sm text-gray-700">#COLLECTION</p>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ $collection->title }}</h1>
                <p class="mt-4 max-w-7xl text-sm text-gray-700">{{ $collection->description }}</p>
                @if ($collection->license)
                    <p class="mt-4 max-w-7xl text-sm text-gray-700">License: {{ $collection->license->title }}</p>
                @endif

                @php
                    $slug = Str::slug($collection->title);
                    $currentYearMonth = now()->format('Y-m');
                @endphp
            
                <a href="https://coconut.s3.uni-jena.de/prod/downloads/{{ $currentYearMonth }}/collections/{{ $slug }}-{{ $currentYearMonth }}.sdf" 
                class="mt-4 inline-block text-sm text-blue-600 underline">
                    Download Collection (SDF) <span aria-hidden="true">→</span>
                </a>
            </div>
        @elseif ($tagType == 'organisms' && $organisms)
            <div x-data="{ showAll: false }" class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
                <p class="mt-4 max-w-xl text-sm text-gray-700">#ORGANISMS</p>
                <ul role="list" class="mt-2 leading-8">
                    @foreach ($organisms as $index => $organism)
                        @if ($organism != '')
                            <li class="inline" x-show="showAll || {{ $index }} < 10">
                                <span class="isolate inline-flex rounded-md shadow-sm mb-2">
                                    <a href="/search?type=tags&amp;q={{ urlencode($organism->name) }}&amp;tagType=organisms"
                                        target="_blank"
                                        class="relative inline-flex items-center rounded-l-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 organism">
                                        {{ $organism->name }}&nbsp;
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m9 9 6-6m0 0 6 6m-6-6v12a6 6 0 0 1-12 0v-3" />
                                        </svg>
                                    </a>
                                    <a href="{{ urldecode($organism->iri) }}" target="_blank"
                                        class="relative -ml-px inline-flex items-center rounded-r-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 capitalize">
                                        {{ $organism->rank }}&nbsp;
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                </span>
                            </li>
                        @endif
                    @endforeach
                </ul>
                @if (count($organisms) > 10)
                    <div class="mt-0">
                        <button @click="showAll = true" x-show="!showAll"
                            class="text-base font-semibold leading-7 text-secondary-dark text-sm">
                            View More ↓
                        </button>
                        <button @click="showAll = false" x-show="showAll"
                            class="text-base font-semibold leading-7 text-secondary-dark  text-sm">
                            View Less ↑
                        </button>
                    </div>
                @endif
            </div>
        @else
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">Browse compounds</h1>
                <p class="mt-4 max-w-xl text-sm text-gray-700">Explore our database of natural products (NPs) to uncover
                    their
                    unique properties. Search, filter, and discover the diverse realm of NP chemistry.
                </p>
            </div>
        @endif
    </div>
    <div class="mx-auto px-4 sm:px-6 lg:max-w-7xl lg:px-8">
        <div class="px-4">
            <div class="sm:hidden">
                <label for="tabs" class="sr-only">Select a tab</label>
                <select id="tabs" name="tabs" x-model="activeTab"
                    class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                    <option value="molecules">Molecules</option>
                    <option value="organism">Organism</option>
                    <option value="citations">Citations</option>
                </select>
            </div>
            <div class="hidden sm:block">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <div @click="activeTab = 'molecules'"
                            class="cursor-pointer whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium"
                            :class="activeTab === 'molecules' ? 'border-indigo-500 text-indigo-600' :
                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                            Molecules
                        </div>
                        <div @click="activeTab = 'organism'"
                            class="cursor-pointer whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium"
                            :class="activeTab === 'organism' ? 'border-indigo-500 text-indigo-600' :
                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                            Organism
                        </div>
                        <div @click="activeTab = 'citations'"
                            class="cursor-pointer whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium"
                            :class="activeTab === 'citations' ? 'border-indigo-500 text-indigo-600' :
                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                            Citations
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Tab content -->
            <div class="py-5" x-show="activeTab === 'molecules'">
                <form wire:submit="search">
                    <div class="bg-white">
                        <div class="mx-auto max-w-7xl">
                            <div class="flex h-16 flex-shrink-0 rounded-md border border-gray-900 border-b-4">
                                <div class="flex flex-1 justify-between md:px-4">
                                    <div class="flex flex-1">
                                        <div class="flex w-full md:ml-0"><label for="search-field"
                                                class="sr-only">Search</label>
                                            <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                                                <div
                                                    class="px-2 pointer-events-none absolute inset-y-0 left-0 flex items-center">
                                                    <svg class="h-5 w-5 flex-shrink-0"
                                                        x-description="Heroicon name: mini/magnifying-glass"
                                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                        fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd"
                                                            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <input name="q" id="q"
                                                    class="h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-900 placeholder-gray-500 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:block"
                                                    wire:model="query"
                                                    placeholder="Search compound name, SMILES, InChI, InChI Key"
                                                    type="search" autofocus="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center md:ml-1">
                                        <livewire:structure-editor :mode="'inline'" :smiles="$query"
                                            lazy="on-load" />
                                        <button type="submit"
                                            class="rounded-md bg-secondary-dark px-3.5 py-1.5 text-base font-semibold leading-7 text-white shadow-sm hover:bg-secondary-light focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 mr-3"><svg
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="size-4 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            </svg>
                                            &nbsp;Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="py-5" x-show="activeTab === 'organism'">
                <form>
                    <div class="bg-white">
                        <div class="mx-auto max-w-7xl">
                            <div class="flex h-16 flex-shrink-0 rounded-md border border-gray-900 border-b-4">
                                <div class="flex flex-1 justify-between md:px-4">
                                    <div class="flex flex-1">
                                        <div class="flex w-full md:ml-0"><label for="search-field"
                                                class="sr-only">Search</label>
                                            <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                                                <div
                                                    class="px-2 pointer-events-none absolute inset-y-0 left-0 flex items-center">
                                                    <svg class="h-5 w-5 flex-shrink-0"
                                                        x-description="Heroicon name: mini/magnifying-glass"
                                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                        fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd"
                                                            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <input name="q" id="q"
                                                    class="h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-900 placeholder-gray-500 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:block"
                                                    wire:model="query"
                                                    placeholder="Search Organisms (Genus or Species or any Taxonomic Rank)"
                                                    type="search" autofocus="">
                                                <input type="hidden" name="tagType" value="organisms">
                                                <input type="hidden" name="type" value="tags">
                                                <input type="hidden" name="activeTab" value="organism">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center md:ml-1">
                                        <button type="submit"
                                            class="rounded-md bg-secondary-dark px-3.5 py-1.5 text-base font-semibold leading-7 text-white shadow-sm hover:bg-secondary-light focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 mr-3"><svg
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="size-4 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            </svg>
                                            &nbsp;Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="py-5" x-show="activeTab === 'citations'">
                <form>
                    <div class="bg-white">
                        <div class="mx-auto max-w-7xl">
                            <div class="flex h-16 flex-shrink-0 rounded-md border border-gray-900 border-b-4">
                                <div class="flex flex-1 justify-between md:px-4">
                                    <div class="flex flex-1">
                                        <div class="flex w-full md:ml-0"><label for="search-field"
                                                class="sr-only">Search</label>
                                            <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                                                <div
                                                    class="px-2 pointer-events-none absolute inset-y-0 left-0 flex items-center">
                                                    <svg class="h-5 w-5 flex-shrink-0"
                                                        x-description="Heroicon name: mini/magnifying-glass"
                                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                        fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd"
                                                            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <input name="q" id="q"
                                                    class="h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-900 placeholder-gray-500 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:block"
                                                    wire:model="query" placeholder="Search DOI or Title"
                                                    type="search" autofocus="">
                                                <input type="hidden" name="tagType" value="citations">
                                                <input type="hidden" name="type" value="tags">
                                                <input type="hidden" name="activeTab" value="citations">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center md:ml-1">
                                        <button type="submit"
                                            class="rounded-md bg-secondary-dark px-3.5 py-1.5 text-base font-semibold leading-7 text-white shadow-sm hover:bg-secondary-light focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 mr-3"><svg
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="size-4 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            </svg>
                                            &nbsp;Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="flex justify-between items-center mx-auto sm:px-6 lg:max-w-7xl lg:px-8">
        <div class="pl-4">
            <livewire:advanced-search />
        </div>
        <div class="flex pr-4">
            <button @click="view = 'card'"
                :class="view === 'card' ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded-l-md font-medium focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
            </button>
            <button @click="view = 'table'"
                :class="view === 'table' ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded-r-md font-medium focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" />
                </svg>

            </button>
        </div>
    </div>
    @if ($molecules && count($molecules) > 0)
        <div class="mx-auto px-4 py-8 sm:px-6 sm:py-8 lg:max-w-7xl lg:px-8">
            <div class="p-4 w-full">
                {{ $molecules->links() }}
            </div>

            <template x-if="view === 'card'">
                <div class="items-center block p-6 bg-white">
                    <div
                        class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
                        @foreach ($molecules as $molecule)
                            <livewire:molecule-card :key="$molecule->identifier" :molecule="$molecule" />
                        @endforeach
                    </div>
                </div>
            </template>
            <template x-if="view === 'table'">
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg border border-gray-200">
                    <table class="w-full rounded-md border-collapse">
                        <tbody>
                            @foreach ($molecules as $molecule)
                                <tr>
                                    <td class="border border-gray-200 px-4 py-2 text-center" style="width: 300px;">
                                        <div class="aspect-h-3 aspect-w-3 sm:aspect-none group-hover:opacity-75">
                                            <livewire:molecule-depict2d :name="$molecule->name" :smiles="$molecule->canonical_smiles">
                                                <a href="{{ route('compound', $molecule->identifier) }}" wire:navigate
                                                    class="text-blue-500 hover:underline">
                                                    {{ $molecule->identifier }}
                                                </a>

                                        </div>
                                    </td>
                                    <td class="border border-gray-200 px-4 py-2 text-left text-wrap align-top pt-5">
                                        <div class="text-wrap truncate text-gray-900 font-bold"
                                            title="{{ $molecule->name }}">
                                            <span class="block text-sm font-medium text-slate-700">Name</span>
                                            {!! convert_italics_notation($molecule->name ? $molecule->name : $molecule->iupac_name) !!}
                                        </div>

                                        <div class="mt-2">
                                            <span class="block text-sm font-medium text-slate-700">Annotation
                                                level</span>
                                            @for ($i = 0; $i < $molecule->annotation_level; $i++)
                                                <span class="text-amber-300">★</span>
                                            @endfor
                                            @for ($i = $molecule->annotation_level; $i < 5; $i++)
                                                ☆
                                            @endfor
                                        </div>
                                        <div class="flex justify-start py-2 mt-1">
                                            <div class="mr-4 flex justify-center items-center text-xs"
                                                title="Organism Count">
                                                <svg fill="currentColor" class="w-5 h-5 inline mr-1" version="1.1"
                                                    id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                                    xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512"
                                                    xml:space="preserve">
                                                    <g>
                                                        <g>
                                                            <circle cx="162.914" cy="197.818" r="11.636" />
                                                        </g>
                                                    </g>
                                                    <g>
                                                        <g>
                                                            <circle cx="221.095" cy="221.091" r="11.636" />
                                                        </g>
                                                    </g>
                                                    <g>
                                                        <g>
                                                            <circle cx="209.459" cy="139.636" r="11.636" />
                                                        </g>
                                                    </g>
                                                    <g>
                                                        <g>
                                                            <path
                                                                d="M453.823,290.909c-6.435,0-11.636,5.213-11.636,11.636c0,25.67-20.876,46.545-46.545,46.545
                               c-44.905,0-81.455,36.538-81.455,81.455c0,32.081-26.1,58.182-58.182,58.182c-32.081,0-58.182-26.1-58.182-58.182v-11.636
                               c0-6.423-5.201-11.636-11.636-11.636c-44.905,0-81.455-36.538-81.455-81.455V139.636c0-44.916,36.55-81.455,81.455-81.455
                               s81.455,36.538,81.455,81.455v186.182c0,28.998-15.604,56.017-40.727,70.54c-5.574,3.212-7.471,10.333-4.259,15.895l17.455,30.231
                               c3.212,5.562,10.345,7.459,15.895,4.259c5.574-3.212,7.482-10.333,4.259-15.895l-11.951-20.713
                               c8.518-6.295,15.825-13.905,22.004-22.307l20.911,12.067c1.827,1.059,3.828,1.559,5.807,1.559c4.026,0,7.936-2.083,10.089-5.818
                               c3.212-5.574,1.303-12.684-4.259-15.895l-20.911-12.067c4.585-10.473,7.494-21.679,8.448-33.292l27.462-9.158
                               c6.086-2.036,9.391-8.623,7.354-14.72c-2.036-6.086-8.553-9.414-14.72-7.354l-19.584,6.516v-30.394h23.273
                               c6.435,0,11.636-5.213,11.636-11.636S320.621,256,314.186,256h-23.273v-49.792l26.95-8.983c6.086-2.036,9.391-8.622,7.354-14.72
                               c-2.036-6.097-8.553-9.414-14.72-7.354l-19.584,6.516v-30.394h23.273c6.435,0,11.636-5.213,11.636-11.636S320.621,128,314.186,128
                               h-23.959c-1.187-10.659-3.991-20.841-8.134-30.301l20.771-11.985c5.562-3.212,7.471-10.321,4.259-15.895
                               c-3.223-5.574-10.345-7.482-15.895-4.259l-20.852,12.044c-6.237-8.448-13.696-15.895-22.144-22.144l12.044-20.852
                               c3.212-5.574,1.303-12.684-4.259-15.895c-5.562-3.212-12.684-1.303-15.895,4.259l-11.985,20.771
                               c-9.472-4.166-19.654-6.959-30.313-8.145V11.636C197.823,5.213,192.621,0,186.186,0S174.55,5.213,174.55,11.636v23.959
                               c-11.497,1.28-22.388,4.48-32.465,9.181l-23.156-24.064c-4.445-4.631-11.811-4.771-16.442-0.314
                               c-4.631,4.457-4.771,11.823-0.314,16.454l19.607,20.375c-7.482,5.865-14.115,12.719-19.77,20.364L81.156,65.559
                               c-5.562-3.223-12.684-1.315-15.907,4.259c-3.223,5.574-1.303,12.684,4.271,15.895l20.771,11.985
                               c-4.154,9.46-6.959,19.642-8.145,30.301H58.186c-6.435,0-11.636,5.213-11.636,11.636s5.201,11.636,11.636,11.636h23.273v38.156
                               l-26.95,8.983c-6.086,2.036-9.391,8.623-7.354,14.72c1.617,4.876,6.156,7.959,11.031,7.959c1.21,0,2.455-0.198,3.677-0.605
                               l19.596-6.516V256H58.186c-6.435,0-11.636,5.213-11.636,11.636s5.201,11.636,11.636,11.636h23.273v30.394l-19.596-6.528
                               c-6.144-2.06-12.684,1.268-14.72,7.354c-2.036,6.097,1.257,12.684,7.354,14.72l27.357,9.123c0.954,11.799,3.91,23.005,8.46,33.385
                               l-20.806,12.009c-5.562,3.223-7.471,10.333-4.259,15.907c2.164,3.735,6.063,5.818,10.089,5.818c1.978,0,3.98-0.5,5.807-1.559
                               l20.783-11.997c6.505,8.809,14.394,16.5,23.284,22.9l-13.766,33.036c-2.479,5.935,0.326,12.742,6.26,15.22
                               c1.466,0.617,2.979,0.908,4.48,0.908c4.561,0,8.879-2.7,10.74-7.168l12.695-30.452c9.076,3.828,18.769,6.447,28.928,7.575v0.628
                               c0,44.916,36.55,81.455,81.455,81.455s81.455-36.538,81.455-81.455c0-32.081,26.1-58.182,58.182-58.182
                               c38.505,0,69.818-31.313,69.818-69.818C465.459,296.122,460.258,290.909,453.823,290.909z" />
                                                        </g>
                                                    </g>
                                                    <g>
                                                        <g>
                                                            <path d="M174.55,256c-25.67,0-46.545,20.876-46.545,46.545s20.876,46.545,46.545,46.545c25.67,0,46.545-20.876,46.545-46.545
                               S200.22,256,174.55,256z M174.55,325.818c-12.835,0-23.273-10.438-23.273-23.273s10.438-23.273,23.273-23.273
                               s23.273,10.438,23.273,23.273S187.385,325.818,174.55,325.818z" />
                                                        </g>
                                                    </g>
                                                </svg>
                                                <span>{{ $molecule->organism_count }}</span>
                                            </div>
                                            <div class="mr-4 flex justify-center items-center text-xs"
                                                title="Collection Count">
                                                <svg class="w-4 h-5 inline mr-1" viewBox="0 0 16 16"
                                                    xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                                    <path
                                                        d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-13z" />
                                                </svg>
                                                <span>{{ $molecule->collection_count }}</span>
                                            </div>
                                            <div class="mr-4 flex justify-center items-center text-xs"
                                                title="Geo Count">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="w-5 h-5 inline mr-1">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                </svg>
                                                <span>{{ $molecule->geo_count }}</span>
                                            </div>
                                            <div class="mr-4 flex justify-center items-center text-xs"
                                                title="Citation Count">
                                                <svg class="w-auto h-3 inline mr-1" viewBox="0 0 16 16"
                                                    xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M6 1h6v7a.5.5 0 0 1-.757.429L9 7.083 6.757 8.43A.5.5 0 0 1 6 8V1z" />
                                                    <path
                                                        d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z" />
                                                    <path
                                                        d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z" />
                                                </svg>
                                                <span>{{ $molecule->citation_count }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </template>
            <div wire:loading role="status"
                class="border rounded-lg shadow-md opacity-90 absolute -translate-x-1/2 top-24 left-1/2 text-center justify-center">
                <img class="w-full rounded-md" alt="loading" src="/img/loading.gif" />
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
</div>
