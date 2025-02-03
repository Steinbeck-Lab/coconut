<div>
    <div class="mt-28 min-h-screen isolate">
        <div class="relative isolate -z-10">
            <div class="mx-auto max-w-4xl lg:max-w-7xl px-4 md:px-10">
                <div class="bg-white rounded-lg border">
                    @if ($molecule->status == 'REVOKED')
                    <div class="rounded-md m-2 bg-red-50 p-4 -mb-5">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"
                                    aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">STATUS: <b>{{ $molecule->status }}</b>
                                </h3>
                                <p class="text-red-800 text-md font-bold">This compound has been removed from the
                                    COCONUT
                                    database due to the lack
                                    of conclusive evidence supporting its classification as a natural product.</p>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul role="list" class="list-disc space-y-1 pl-5">
                                        <li>{{ $molecule->comment[0]['comment'] }} <br />
                                            <date>Last update: {{ $molecule->comment[0]['timestamp'] }}
                                                <date>
                                        </li>
                                    </ul>
                                </div>
                                <a class="mt-5 mb-3 relative inline-flex items-center gap-x-1.5 rounded-md bg-red-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500"
                                    href="/dashboard/reports/create?compound_id={{ $molecule->identifier }}&type=change">Request
                                    changes to this page <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="lg:py-10 py-5 mx-auto max-w-3xl px-4 sm:px-6 md:flex md:items-center md:justify-between md:space-x-5 lg:max-w-7xl lg:px-8">
                        <div class="flex items-center space-x-5">
                            <div>
                                <p class="text-secondary-dark text-lg my-0">{{ $molecule->identifier }}</p>
                                <h1 class="mb-2 text-2xl break-all font-bold leading-7 break-words text-gray-900 sm:text-3xl sm:tracking-tight">
                                    {!! convert_italics_notation($molecule->name ? $molecule->name : $molecule->iupac_name) !!}
                                </h1>
                                <p class="text-sm font-medium text-gray-500">Created on <time datetime="{{ $molecule->created_at }}">{{ $molecule->created_at }}</time>
                                    &middot; Last update on <time datetime="{{ $molecule->updated_at }}">{{ $molecule->updated_at }}</time>
                                </p>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-shrink-0 md:mt-0">
                            @auth
                            @if(auth()->user()->roles->isNotEmpty())
                            <a href="/dashboard/molecules/{{ $molecule->id }}/edit" target="_blank"  class="inline-flex items-center px-4 py-2 border border-secondary-dark rounded-md shadow-sm text-sm font-medium text-white bg-secondary-dark hover:bg-green-700">
                                <svg class="-ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </a>
                            @endif
                            @endauth
                        </div>
                    </div>
                    @if ($molecule->properties)
                    <div class="border-b border-b-gray-900/10 lg:border-t lg:border-t-gray-900/5">
                        <dl
                            class="mx-auto grid max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:px-2 xl:px-0">
                            <div
                                class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-5 lg:py-10 sm:px-6 lg:border-t-0 xl:px-8 ">
                                <dt class="font-medium text-gray-500"> NPLikeness
                                    <div class="tooltip">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="currentColor" aria-hidden="true"
                                            class="h-5 w-5 -mt-1 inline cursor-pointer">
                                            <path fill-rule="evenodd"
                                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 11-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="tooltiptext">NP Likeness Score: The likelihood of the compound
                                            to be a
                                            natural
                                            product, ranges from -5 (less likely) to 5 (very likely).</span>
                                    </div>
                                </dt>
                                <div data-v-5784ed69="" class="inline-block"
                                    style="border: 30px solid transparent; margin: -30px; --c81fc0a4: 9999;">
                                    <div data-v-5784ed69=""><span>
                                            <div class="wrap">
                                                @foreach (range(0, ceil(npScore($molecule->properties->np_likeness))) as $i)
                                                <div></div>
                                                @endforeach
                                            </div>
                                            <span
                                                class="ml-1 text-sm font-bold">{{ $molecule->properties->np_likeness }}</span>
                                        </span></div>
                                </div>
                            </div>
                            <div
                                class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-5 lg:py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                                <div>
                                    <dt class="font-medium text-gray-500"> Annotation Level</dt>
                                    <div class="flex items-center">
                                        @for ($i = 0; $i < $molecule->annotation_level; $i++)
                                            <span class="text-amber-300">★<span>
                                                    @endfor
                                                    @for ($i = $molecule->annotation_level; $i < 5; $i++)
                                                        ☆
                                                        @endfor
                                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-5 lg:py-10 sm:px-6 lg:border-t-0 xl:px-8 lg:border-l">
                                    <div>
                                        <dt class="font-medium text-gray-500">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    Mol. Weight
                                                </div>
                                                <div x-data="{ tooltip: false }" x-on:mouseover="tooltip = true"
                                                    x-on:mouseleave="tooltip = false"
                                                    class="ml-2 h-5 w-5 cursor-pointer">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <div x-show="tooltip"
                                                        class="text-sm text-white absolute bg-green-400 rounded-lg p-2 transform -translate-y-8 translate-x-8">
                                                        Exact Isotopic Mass is calculated using RDKit - <a
                                                            href="https://www.rdkit.org/docs/source/rdkit.Chem.Descriptors.html">https://www.rdkit.org/docs/source/rdkit.Chem.Descriptors.html</a>
                                                    </div>
                                                </div>
                                            </div>

                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ $molecule->properties->exact_molecular_weight }}
                                        </dd>
                                    </div>
                                </div>
                                <div
                                    class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-5 lg:py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                                    <div>
                                        <dt class="font-medium text-gray-500 text-gray-500"> Mol. Formula </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ $molecule->properties->molecular_formula }}
                                        </dd>
                                    </div>
                                </div>
                        </dl>
                    </div>
                    @endif
                    <div
                        class="mx-auto mt-8 grid max-w-3xl grid-cols-1 gap-6 sm:px-6 lg:max-w-7xl lg:grid-flow-col-dense lg:grid-cols-3 px-4">
                        <section class="space-y-6 lg:col-span-2 lg:col-start-1 order-2 lg:order-1">
                            @if ($molecule->organisms && count($molecule->organisms) > 0)
                            <section>
                                <div class="bg-white border shadow sm:rounded-lg" x-data="{ showAll: false, searchTerm: '' }">
                                    <div class="px-4 py-5 sm:px-6">
                                        <h2 id="applicant-information-title"
                                            class="text-lg font-medium leading-6 text-gray-900">
                                            Organisms ({{ count($molecule->organisms) }})
                                        </h2>
                                    </div>
                                    <div class="border-t border-gray-200">
                                        <div class="no-scrollbar px-4 py-4 lg:px-8 min-w-0">
                                            <!-- Search Bar -->
                                            <div class="mb-4">
                                                <input type="text" x-model="searchTerm"
                                                    placeholder="Search organisms..."
                                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-secondary-dark focus:ring-secondary-dark sm:text-sm" />
                                            </div>
                                            <ul role="list" class="mt-2 leading-8">
                                                @foreach ($molecule->organisms as $index => $organism)
                                                @if ($organism != '')
                                                <li class="inline"
                                                    x-show="(showAll || {{ $index }} < 10) && (searchTerm === '' || '{{ strtolower($organism->name) }}'.includes(searchTerm.toLowerCase()))">
                                                    <span
                                                        class="isolate inline-flex rounded-md shadow-sm mb-2">
                                                        <a href="/search?type=tags&amp;q={{ urlencode($organism->name) }}&amp;tagType=organisms"
                                                            target="_blank"
                                                            class="relative inline-flex items-center rounded-l-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 organism">
                                                            {{ $organism->name }}&nbsp;
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke-width="1.5" stroke="currentColor"
                                                                class="size-4">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    d="m9 9 6-6m0 0 6 6m-6-6v12a6 6 0 0 1-12 0v-3" />
                                                            </svg>
                                                        </a>
                                                        <a href="{{ urldecode($organism->iri) }}"
                                                            target="_blank"
                                                            class="relative -ml-px inline-flex items-center rounded-r-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 capitalize">
                                                            {{ $organism->rank }}&nbsp;
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke-width="1.5" stroke="currentColor"
                                                                class="size-4">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                            </svg>
                                                        </a>
                                                    </span>
                                                </li>
                                                @endif
                                                @endforeach
                                            </ul>
                                            @if (count($molecule->organisms) > 10)
                                            <div class="mt-4">
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
                                    </div>
                                </div>
                            </section>
                            @endif
                            @if ($molecule->geo_locations && count($molecule->geo_locations) > 0)
                            <section>
                                <div class="bg-white border shadow sm:rounded-lg">
                                    <div class="px-4 py-5 sm:px-6">
                                        <h2 id="applicant-information-title"
                                            class="text-lg font-medium leading-6 text-gray-900">
                                            Geolocations</h2>
                                    </div>
                                    <div class="border-t border-gray-200">
                                        <div class="no-scrollbar px-4 py-4 lg:px-8 min-w-0">
                                            <ul role="list" class="mt-2 leading-8">
                                                @foreach ($molecule->geo_locations as $geo_location)
                                                @if ($geo_location != '')
                                                <li class="inline">
                                                    <span
                                                        class="text-sm relative mr-2 inline-flex items-center rounded-md border border-gray-300 px-3 py-0.5"
                                                        target="_blank">
                                                        {{ $geo_location->name }}
                                                    </span>
                                                </li>
                                                @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            @endif

                            <section>
                                <div class="bg-white border shadow sm:rounded-lg">
                                    <div class="px-4 py-5 sm:px-6">
                                        <h2 id="applicant-information-title"
                                            class="text-lg font-medium leading-6 text-gray-900">
                                            Representations</h2>
                                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Molecular details</p>
                                    </div>
                                    <div class="border-t border-gray-200">
                                        <div class="no-scrollbar px-4 lg:px-8 min-w-0">
                                            <article>
                                                <div class="">
                                                    <section id="representations" class="my-4">
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <dt
                                                                class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                                COCONUT id
                                                            </dt>
                                                            <div class="mt-1 break-all text-sm text-gray-900">
                                                                {{ $molecule->identifier }}
                                                                <span class="float-end mr-2 group-hover:block hidden">
                                                                    <livewire:copy-button
                                                                        text-to-copy="{{ $molecule->identifier }}" />
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <dt
                                                                class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                                Name
                                                            </dt>
                                                            <div class="mt-1 break-all text-sm text-gray-900">
                                                                {!! convert_italics_notation($molecule->name ? $molecule->name : '-') !!}
                                                                <span class="float-end mr-2 group-hover:block hidden">
                                                                    <livewire:copy-button
                                                                        text-to-copy="{{ $molecule->name }}" />
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <dt
                                                                class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                                IUPAC name
                                                            </dt>
                                                            <div class="mt-1 break-all text-sm text-gray-900">
                                                                {!! convert_italics_notation($molecule->iupac_name) !!}
                                                                <span class="float-end mr-2 group-hover:block hidden">
                                                                    <livewire:copy-button
                                                                        text-to-copy="{{ remove_italics_notation($molecule->iupac_name) }}" />
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <dt
                                                                class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                                InChI
                                                            </dt>
                                                            <div class="mt-1 break-all text-sm text-gray-900">
                                                                {{ $molecule->standard_inchi }}
                                                                <span class="float-end mr-2 group-hover:block hidden">
                                                                    <livewire:copy-button
                                                                        text-to-copy="{{ $molecule->standard_inchi }}" />
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <dt
                                                                class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                                InChIKey
                                                            </dt>
                                                            <div class="mt-1 break-all text-sm text-gray-900">
                                                                {{ $molecule->standard_inchi_key }}
                                                                <span class="float-end mr-2 group-hover:block hidden">
                                                                    <livewire:copy-button
                                                                        text-to-copy="{{ $molecule->standard_inchi_key }}" />
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <dt
                                                                class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                                Canonical SMILES (RDKit)
                                                            </dt>
                                                            <div class="mt-1 break-all text-sm text-gray-900">
                                                                {{ $molecule->canonical_smiles }}
                                                                <span class="float-end mr-2 group-hover:block hidden">
                                                                    <livewire:copy-button
                                                                        text-to-copy="{{ $molecule->canonical_smiles }}" />
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @if ($molecule->properties)
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <div class="sm:flex sm:justify-between">
                                                                <div class="text-sm font-medium text-gray-500">
                                                                    Murcko
                                                                    Framework
                                                                </div>
                                                            </div>
                                                            <div class="mt-1 break-all text-sm text-gray-900">
                                                                {{ $molecule->properties->murcko_framework ? $molecule->properties->murcko_framework : '-' }}
                                                                <span
                                                                    class="float-end mr-2 group-hover:block hidden">
                                                                    <livewire:copy-button
                                                                        text-to-copy="{{ $molecule->murcko_framework }}" />
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @endif
                                                        @if ($molecule->synonyms && count($molecule->synonyms) > 0)
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <dt
                                                                class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                                Synonyms
                                                            </dt>

                                                            <div x-data="{ showAll: false }">
                                                                <div class="no-scrollbar min-w-0">
                                                                    <ul role="list" class="mt-2 leading-8">
                                                                        @foreach ($molecule->synonyms as $index => $synonym)
                                                                        @if ($synonym != '')
                                                                        <li class="inline"
                                                                            x-show="showAll || {{ $index }} < 10">
                                                                            <span
                                                                                class="border px-4 bg-white isolate inline-flex rounded-md shadow-sm mb-2">
                                                                                {{ $synonym }}
                                                                            </span>
                                                                        </li>
                                                                        @endif
                                                                        @endforeach
                                                                    </ul>
                                                                    @if ($molecule->synonym_count > 10)
                                                                    <div class="mt-4">
                                                                        <button @click="showAll = true"
                                                                            x-show="!showAll"
                                                                            class="text-base font-semibold leading-7 text-secondary-dark text-sm">
                                                                            View More ↓
                                                                        </button>
                                                                        <button @click="showAll = false"
                                                                            x-show="showAll"
                                                                            class="text-base font-semibold leading-7 text-secondary-dark  text-sm">
                                                                            View Less ↑
                                                                        </button>
                                                                    </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endif
                                                        @if ($molecule->cas && count($molecule->cas) > 0)
                                                        <div class="group -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                            <dt
                                                                class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                                CAS
                                                            </dt>

                                                            <div x-data="{ showAll: false }">
                                                                <div class="no-scrollbar min-w-0">
                                                                    <ul role="list" class="mt-2 leading-8">
                                                                        @foreach ($molecule->cas as $index => $cas)
                                                                        @if ($cas != '')
                                                                        <li class="inline"
                                                                            x-show="showAll || {{ $index }} < 10">
                                                                            <span
                                                                                class="border px-4 bg-white isolate inline-flex rounded-md shadow-sm mb-2">
                                                                                {{ $cas }}
                                                                            </span>
                                                                        </li>
                                                                        @endif
                                                                        @endforeach
                                                                    </ul>
                                                                    @if (count($molecule->cas) > 10)
                                                                    <div class="mt-4">
                                                                        <button @click="showAll = true"
                                                                            x-show="!showAll"
                                                                            class="text-base font-semibold leading-7 text-secondary-dark text-sm">
                                                                            View More ↓
                                                                        </button>
                                                                        <button @click="showAll = false"
                                                                            x-show="showAll"
                                                                            class="text-base font-semibold leading-7 text-secondary-dark  text-sm">
                                                                            View Less ↑
                                                                        </button>
                                                                    </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </section>
                                                </div>
                                            </article>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            @if ($molecule->properties)
                            <section aria-labelledby="notes-title">
                                <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                                    <div class="divide-y divide-gray-200">
                                        <div class="px-4 py-5 sm:px-6">
                                            <h2 id="notes-title" class="text-lg font-medium text-gray-900">
                                                Chemical
                                                classification
                                            </h2>
                                        </div>
                                        <div class="px-4 py-6 sm:px-6">
                                            <ul role="list" class="px-0">
                                                <li class="py-1 flex md:py-0"><span class="ml-3 text-base">
                                                        <b>Super class</b>:
                                                        <a class="hover:text-blue-600 hover:underline"
                                                            target="_blank"
                                                            href="../search?q=superclass%3A{{ $molecule->properties && $molecule->properties['chemical_super_class'] ? Str::slug($molecule->properties['chemical_super_class']) : '-' }}&amp;page=1&amp;type=filters">
                                                            {{ $molecule->properties && $molecule->properties['chemical_super_class'] ? $molecule->properties['chemical_super_class'] : '-' }}
                                                        </a>
                                                    </span>
                                                </li>
                                                <li class="py-1 flex md:py-0"><span
                                                        class="ml-3 text-base"><b>Class</b>:
                                                        <a class="hover:text-blue-600 hover:underline"
                                                            target="_blank"
                                                            href="../search?q=class%3A{{ $molecule->properties && $molecule->properties['chemical_class'] ? Str::slug($molecule->properties['chemical_class']) : '-' }}&amp;page=1&amp;type=filters">
                                                            {{ $molecule->properties && $molecule->properties['chemical_class'] ? $molecule->properties['chemical_class'] : '-' }}</span>
                                                    </a>
                                                </li>
                                                <li class="py-1 flex md:py-0"><span class="ml-3 text-base"><b>Sub
                                                            class</b>:
                                                        <a class="hover:text-blue-600 hover:underline"
                                                            target="_blank"
                                                            href="../search?q=subclass%3A{{ $molecule->properties && $molecule->properties['chemical_sub_class'] ? Str::slug($molecule->properties['chemical_sub_class']) : '-' }}&amp;page=1&amp;type=filters">
                                                            {{ $molecule->properties && $molecule->properties['chemical_sub_class'] ? $molecule->properties['chemical_sub_class'] : '-' }}
                                                        </a>
                                                    </span>
                                                </li>
                                                <li class="py-1 flex md:py-0"><span
                                                        class="ml-3 text-base"><b>Direct
                                                            parent</b>:
                                                        <a class="hover:text-blue-600 hover:underline"
                                                            target="_blank"
                                                            href="../search?q=parent%3A{{ $molecule->properties && $molecule->properties['direct_parent_classification'] ? Str::slug($molecule->properties['direct_parent_classification']) : '-' }}&amp;page=1&amp;type=filters">
                                                            {{ $molecule->properties && $molecule->properties['direct_parent_classification'] ? $molecule->properties['direct_parent_classification'] : '-' }}
                                                        </a>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            @endif

                            @if ($molecule->properties)
                            <section aria-labelledby="notes-title">
                                <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                                    <div class="divide-y divide-gray-200">
                                        <div class="px-4 py-5 sm:px-6">
                                            <h2 id="notes-title" class="text-lg font-medium text-gray-900">NP
                                                Classification
                                            </h2>
                                        </div>
                                        <div class="px-4 py-6 sm:px-6">
                                            <ul role="list" class="px-0">
                                                <li class="py-1 flex md:py-0"><span class="ml-3 text-base">
                                                        <b>Pathway</b>:
                                                        <a class="hover:text-blue-600 hover:underline"
                                                            target="_blank"
                                                            href="../search?q=np_pathway%3A{{ $molecule->properties && $molecule->properties['np_classifier_pathway'] ? Str::slug($molecule->properties['np_classifier_pathway']) : '-' }}&amp;page=1&amp;type=filters">
                                                            {{ $molecule->properties && $molecule->properties['np_classifier_pathway'] ? $molecule->properties['np_classifier_pathway'] : '-' }}
                                                        </a>
                                                    </span>
                                                </li>
                                                <li class="py-1 flex md:py-0"><span
                                                        class="ml-3 text-base"><b>Super Class</b>:
                                                        <a class="hover:text-blue-600 hover:underline"
                                                            target="_blank"
                                                            href="../search?q=np_superclass%3A{{ $molecule->properties && $molecule->properties['np_classifier_superclass'] ? Str::slug($molecule->properties['np_classifier_superclass']) : '-' }}&amp;page=1&amp;type=filters">
                                                            {{ $molecule->properties && $molecule->properties['np_classifier_superclass'] ? $molecule->properties['np_classifier_superclass'] : '-' }}</span>
                                                    </a>
                                                </li>
                                                <li class="py-1 flex md:py-0"><span
                                                        class="ml-3 text-base"><b>Class</b>:
                                                        <a class="hover:text-blue-600 hover:underline"
                                                            target="_blank"
                                                            href="../search?q=np_class%3A{{ $molecule->properties && $molecule->properties['np_classifier_class'] ? Str::slug($molecule->properties['np_classifier_class']) : '-' }}&amp;page=1&amp;type=filters">
                                                            {{ $molecule->properties && $molecule->properties['np_classifier_class'] ? $molecule->properties['np_classifier_class'] : '-' }}
                                                        </a>
                                                    </span>
                                                </li>
                                                <li class="py-1 flex md:py-0"><span class="ml-3 text-base"><b>Is
                                                            glycoside</b>:
                                                        <a class="hover:text-blue-600 hover:underline"
                                                            target="_blank"
                                                            href="../search?q=np_glycoside%3A{{ $molecule->properties && $molecule->properties['np_classifier_is_glycoside'] ? Str::slug($molecule->properties['np_classifier_is_glycoside']) : 'false' }}&amp;page=1&amp;type=filters">
                                                            {{ $molecule->properties && $molecule->properties['np_classifier_is_glycoside'] ? 'True' : 'False' }}
                                                        </a>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            @endif


                            <section aria-labelledby="notes-title">
                                <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                                    <div class="divide-y divide-gray-200">
                                        <div class="px-4 py-5 sm:px-6">
                                            <h2 id="notes-title" class="text-lg font-medium text-gray-900">References
                                            </h2>
                                        </div>
                                        <section>
                                            <div class="px-4 py-6 sm:px-6">
                                                <h2 id="notes-title" class="mb-2 text-lg font-medium text-gray-900">
                                                    Citations
                                                </h2>
                                                @if (count($molecule->citations) > 0)
                                                <div x-data="{ showAllCitations: false }">
                                                    <div class="not-prose grid grid-cols-1 gap-6 sm:grid-cols-2">
                                                        @foreach ($molecule->citations as $index => $citation)
                                                        @if ($citation->title != '')
                                                        <div class="group relative rounded-xl border border-slate-200"
                                                            x-show="showAllCitations || {{ $index }} < 6">
                                                            <div
                                                                class="absolute -inset-px rounded-xl border-2 border-transparent opacity-0 [background:linear-gradient(var(--quick-links-hover-bg,theme(colors.sky.50)),var(--quick-links-hover-bg,theme(colors.sky.50)))_padding-box,linear-gradient(to_top,theme(colors.red.400),theme(colors.cyan.400),theme(colors.sky.500))_border-box] group-hover:opacity-100">
                                                            </div>
                                                            <div
                                                                class="relative rounded-xl p-6">
                                                                <svg aria-hidden="true"
                                                                    viewBox="0 0 32 32" fill="none"
                                                                    class="h-8 w-8 [--icon-foreground:theme(colors.slate.900)] [--icon-background:theme(colors.white)]">
                                                                    <defs>
                                                                        <radialGradient cx="0"
                                                                            cy="0" r="1"
                                                                            gradientUnits="userSpaceOnUse"
                                                                            id=":R1k19n6:-gradient"
                                                                            gradientTransform="matrix(0 21 -21 0 12 11)">
                                                                            <stop stop-color="#0EA5E9">
                                                                            </stop>
                                                                            <stop stop-color="#22D3EE"
                                                                                offset=".527">
                                                                            </stop>
                                                                            <stop stop-color="#818CF8"
                                                                                offset="1">
                                                                            </stop>
                                                                        </radialGradient>
                                                                        <radialGradient cx="0"
                                                                            cy="0" r="1"
                                                                            gradientUnits="userSpaceOnUse"
                                                                            id=":R1k19n6:-gradient-dark"
                                                                            gradientTransform="matrix(0 24.5 -24.5 0 16 5.5)">
                                                                            <stop stop-color="#0EA5E9">
                                                                            </stop>
                                                                            <stop stop-color="#22D3EE"
                                                                                offset=".527">
                                                                            </stop>
                                                                            <stop stop-color="#818CF8"
                                                                                offset="1">
                                                                            </stop>
                                                                        </radialGradient>
                                                                    </defs>
                                                                    <g class="">
                                                                        <circle cx="12"
                                                                            cy="20" r="12"
                                                                            fill="url(#:R1k19n6:-gradient)">
                                                                        </circle>
                                                                        <path
                                                                            d="M27 12.13 19.87 5 13 11.87v14.26l14-14Z"
                                                                            class="fill-[var(--icon-background)] stroke-[color:var(--icon-foreground)]"
                                                                            fill-opacity="0.5"
                                                                            stroke-width="2"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"></path>
                                                                        <path
                                                                            d="M3 3h10v22a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V3Z"
                                                                            class="fill-[var(--icon-background)]"
                                                                            fill-opacity="0.5"></path>
                                                                        <path
                                                                            d="M3 9v16a4 4 0 0 0 4 4h2a4 4 0 0 0 4-4V9M3 9V3h10v6M3 9h10M3 15h10M3 21h10"
                                                                            class="stroke-[color:var(--icon-foreground)]"
                                                                            stroke-width="2"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"></path>
                                                                        <path
                                                                            d="M29 29V19h-8.5L13 26c0 1.5-2.5 3-5 3h21Z"
                                                                            fill-opacity="0.5"
                                                                            class="fill-[var(--icon-background)] stroke-[color:var(--icon-foreground)]"
                                                                            stroke-width="2"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"></path>
                                                                    </g>
                                                                    <g class="hidden">
                                                                        <path fill-rule="evenodd"
                                                                            clip-rule="evenodd"
                                                                            d="M3 2a1 1 0 0 0-1 1v21a6 6 0 0 0 12 0V3a1 1 0 0 0-1-1H3Zm16.752 3.293a1 1 0 0 0-1.593.244l-1.045 2A1 1 0 0 0 17 8v13a1 1 0 0 0 1.71.705l7.999-8.045a1 1 0 0 0-.002-1.412l-6.955-6.955ZM26 18a1 1 0 0 0-.707.293l-10 10A1 1 0 0 0 16 30h13a1 1 0 0 0 1-1V19a1 1 0 0 0-1-1h-3ZM5 18a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H5Zm-1-5a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1Zm1-7a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2H5Z"
                                                                            fill="url(#:R1k19n6:-gradient-dark)">
                                                                        </path>
                                                                    </g>
                                                                </svg>
                                                                <h2
                                                                    class="mt-2 font-bold text-base text-gray-900">
                                                                    <a target="_blank"
                                                                        href="https://doi.org/{{ $citation->doi }}">
                                                                        <span
                                                                            class="rounded-xl"></span>{{ $citation->title }}
                                                                    </a>
                                                                </h2>
                                                                <h2
                                                                    class="mt-2 font-display text-base text-slate-900">
                                                                    <a target="_blank"
                                                                        href="https://doi.org/{{ $citation->doi }}">
                                                                        <span
                                                                            class="rounded-xl"></span>{{ $citation->authors }}
                                                                    </a>
                                                                </h2>
                                                                <h2
                                                                    class="mt-2 font-display text-base text-slate-900">
                                                                    <a target="_blank"
                                                                        href="https://doi.org/{{ $citation->doi }}">
                                                                        <span
                                                                            class="rounded-xl"></span>DOI: {{ $citation->doi }}
                                                                    </a>
                                                                    <span class="ml-3 mr-4">
                                                                        <livewire:copy-button
                                                                            text-to-copy="{{ $citation->doi }}" />
                                                                    </span>
                                                                </h2>
                                                                <h2
                                                                    class="mt-2 font-display text-base text-slate-900">
                                                                    <a target="_blank"
                                                                        href="https://www.lens.org/lens/search/scholar/list?q=ids.doi:{{ $citation->doi }}">
                                                                        <span
                                                                            class="rounded-xl"></span>Lens.org <svg xmlns="http://www.w3.org/2000/svg"
                                                                            fill="none"
                                                                            viewBox="0 0 24 24"
                                                                            stroke-width="1.5"
                                                                            stroke="currentColor"
                                                                            class="size-4 inline">
                                                                            <path
                                                                                stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25">
                                                                            </path>
                                                                        </svg>
                                                                    </a>
                                                                    <span x-data="{ tooltip: false }"
                                                                        x-on:mouseover="tooltip = true"
                                                                        x-on:mouseleave="tooltip = false"
                                                                        class="h-5 w-5 cursor-pointer inline">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-5 w-5 inline" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                        </svg>
                                                                        <div x-show="tooltip"
                                                                            class="text-sm text-white absolute bg-green-400 rounded-lg p-2 transform -translate-y-8 translate-x-8 z-10">
                                                                            The Lens serves all the patents and scholarly work in the world as a free, open and secure digital public good, with user privacy a paramount focus.
                                                                        </div>
                                                                    </span>
                                                                </h2>
                                                            </div>
                                                        </div>
                                                        @endif
                                                        @endforeach

                                                    </div>
                                                    @if (count($molecule->citations) > 6)
                                                    <div class="flex justify-center mt-4">
                                                        <button @click="showAllCitations = true"
                                                            x-show="!showAllCitations"
                                                            class="text-base font-semibold leading-7 text-secondary-dark text-sm">
                                                            View More ↓
                                                        </button>
                                                        <button @click="showAllCitations = false"
                                                            x-show="showAllCitations"
                                                            class="text-base font-semibold leading-7 text-secondary-dark text-sm">
                                                            View Less ↑
                                                        </button>
                                                    </div>
                                                    @endif
                                                </div>
                                                @else
                                                <p class="text-gray-400">No citations</p>
                                                @endif

                                                <h2 id="notes-title"
                                                    class="mb-2 mt-4 text-lg font-medium text-gray-900">
                                                    Collections</h2>
                                                @if (count($molecule->collections) > 0)
                                                <div class="not-prose grid grid-cols-1 gap-6 sm:grid-cols-1"
                                                    x-data="{ showAllCollections: false }">
                                                    @foreach ($molecule->collections as $index => $collection)
                                                    <div x-data="{ collection: {{ $collection }} }"
                                                        x-show="showAllCollections || {{ $index }} < 6">
                                                        <div
                                                            class="group rounded-xl border border-slate-200">
                                                            <div
                                                                class="overflow-hidden rounded-xl p-6">
                                                                <svg aria-hidden="true" viewBox="0 0 32 32"
                                                                    fill="none"
                                                                    class="mb-2 h-8 w-8 [--icon-foreground:theme(colors.slate.900)] [--icon-background:theme(colors.white)]">
                                                                    <defs>
                                                                        <radialGradient cx="0"
                                                                            cy="0" r="1"
                                                                            gradientUnits="userSpaceOnUse"
                                                                            id=":R1k19n6:-gradient"
                                                                            gradientTransform="matrix(0 21 -21 0 12 11)">
                                                                            <stop stop-color="#0EA5E9"></stop>
                                                                            <stop stop-color="#22D3EE"
                                                                                offset=".527">
                                                                            </stop>
                                                                            <stop stop-color="#818CF8"
                                                                                offset="1">
                                                                            </stop>
                                                                        </radialGradient>
                                                                        <radialGradient cx="0"
                                                                            cy="0" r="1"
                                                                            gradientUnits="userSpaceOnUse"
                                                                            id=":R1k19n6:-gradient-dark"
                                                                            gradientTransform="matrix(0 24.5 -24.5 0 16 5.5)">
                                                                            <stop stop-color="#0EA5E9"></stop>
                                                                            <stop stop-color="#22D3EE"
                                                                                offset=".527">
                                                                            </stop>
                                                                            <stop stop-color="#818CF8"
                                                                                offset="1">
                                                                            </stop>
                                                                        </radialGradient>
                                                                    </defs>
                                                                    <g class="">
                                                                        <circle cx="12" cy="20"
                                                                            r="12"
                                                                            fill="url(#:R1k19n6:-gradient)">
                                                                        </circle>
                                                                        <path
                                                                            d="M27 12.13 19.87 5 13 11.87v14.26l14-14Z"
                                                                            class="fill-[var(--icon-background)] stroke-[color:var(--icon-foreground)]"
                                                                            fill-opacity="0.5"
                                                                            stroke-width="2"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round">
                                                                        </path>
                                                                        <path
                                                                            d="M3 3h10v22a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V3Z"
                                                                            class="fill-[var(--icon-background)]"
                                                                            fill-opacity="0.5"></path>
                                                                        <path
                                                                            d="M3 9v16a4 4 0 0 0 4 4h2a4 4 0 0 0 4-4V9M3 9V3h10v6M3 9h10M3 15h10M3 21h10"
                                                                            class="stroke-[color:var(--icon-foreground)]"
                                                                            stroke-width="2"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"></path>
                                                                        <path
                                                                            d="M29 29V19h-8.5L13 26c0 1.5-2.5 3-5 3h21Z"
                                                                            fill-opacity="0.5"
                                                                            class="fill-[var(--icon-background)] stroke-[color:var(--icon-foreground)]"
                                                                            stroke-width="2"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"></path>
                                                                    </g>
                                                                    <g class="hidden">
                                                                        <path fill-rule="evenodd"
                                                                            clip-rule="evenodd"
                                                                            d="M3 2a1 1 0 0 0-1 1v21a6 6 0 0 0 12 0V3a1 1 0 0 0-1-1H3Zm16.752 3.293a1 1 0 0 0-1.593.244l-1.045 2A1 1 0 0 0 17 8v13a1 1 0 0 0 1.71.705l7.999-8.045a1 1 0 0 0-.002-1.412l-6.955-6.955ZM26 18a1 1 0 0 0-.707.293l-10 10A1 1 0 0 0 16 30h13a1 1 0 0 0 1-1V19a1 1 0 0 0-1-1h-3ZM5 18a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H5Zm-1-5a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1Zm1-7a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2H5Z"
                                                                            fill="url(#:R1k19n6:-gradient-dark)">
                                                                        </path>
                                                                    </g>
                                                                </svg>
                                                                <a href="/search?type=tags&amp;q={{ $collection->title }}&amp;tagType=dataSource"
                                                                    class="hover:pointer font-bold text-base text-xl text-gray-900">
                                                                    {{ $collection->title }} <svg
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke-width="1.5"
                                                                        stroke="currentColor"
                                                                        class="size-4 inline">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            d="m9 9 6-6m0 0 6 6m-6-6v12a6 6 0 0 1-12 0v-3">
                                                                        </path>
                                                                    </svg>
                                                                </a>
                                                                <h2 x-show="collection.description"
                                                                    class="mt-2 font-display text-base text-slate-900">
                                                                    {{ $collection->description }}
                                                                </h2>
                                                                <h2 x-show="collection.doi"
                                                                    class="mt-2 font-display text-base text-slate-900">
                                                                    {{ $collection->doi }}
                                                                </h2>
                                                                <div x-show="collection.pivot.reference"
                                                                    class="mt-1 font-display text-base text-slate-900">
                                                                    @foreach ($this->getReferenceUrls($collection->pivot) as $key => $item)
                                                                    @foreach ($item as $reference => $url)
                                                                    <span class="inline-flex rounded-md shadow-xs mb-2">
                                                                        <livewire:entry-details-display :mol="$molecule" :collection="$collection" :reference="$reference" lazy="on-load" />

                                                                        @if (!empty($url))
                                                                        <a target="_blank" href="{{ $url }}" type="button" class="-ml-px inline-flex items-center bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                fill="none"
                                                                                viewBox="0 0 24 24"
                                                                                stroke-width="1.5"
                                                                                stroke="currentColor"
                                                                                class="size-4 inline text-gray-900">
                                                                                <path
                                                                                    stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25">
                                                                                </path>
                                                                            </svg>
                                                                        </a>
                                                                        @endif
                                                                        @if (!empty($reference))
                                                                        <div class="-ml-px inline-flex items-center bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 rounded-r-md ring-gray-300 ring-inset hover:bg-gray-50">
                                                                            <span class="ml-2 mr-1">
                                                                                <livewire:copy-button
                                                                                    :key="'copy-button-' . $loop->index"
                                                                                    text-to-copy="{{ $reference }}" />
                                                                            </span>
                                                                        </div>
                                                                        @endif
                                                                    </span>
                                                                    @endforeach
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                    @if (count($molecule->collections) > 6)
                                                    <div class="flex justify-center mt-4">
                                                        <button @click="showAllCollections = true"
                                                            x-show="!showAllCollections"
                                                            class="text-base font-semibold leading-7 text-secondary-dark text-sm">
                                                            View More ↓
                                                        </button>
                                                        <button @click="showAllCollections = false"
                                                            x-show="showAllCollections"
                                                            class="text-base font-semibold leading-7 text-secondary-dark text-sm">
                                                            View Less ↑
                                                        </button>
                                                    </div>
                                                    @endif
                                                </div>
                                                @else
                                                <p class="text-gray-400">No collections</p>
                                                @endif
                                            </div>
                                        </section>

                                    </div>
                                </div>
                            </section>

                            @if ($molecule->related && count($molecule->related) > 0)
                            <section aria-labelledby="notes-title">
                                <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                                    <div class="divide-y divide-gray-200">
                                        <div class="px-4 py-5 sm:px-6">
                                            <h2 id="notes-title" class="text-lg font-medium text-gray-900">
                                                Tautomers</h2>
                                        </div>
                                        <div class="px-4 pb-5 sm:px-6">
                                            <div
                                                class="mx-auto grid mt-6 gap-5 lg:max-w-none md:grid-cols-3 lg:grid-cols-2">
                                                @foreach ($molecule->related as $tautomer)
                                                <livewire:molecule-card :molecule="$tautomer" lazy />
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            @endif

                            @if ($molecule->is_parent && $molecule->has_variants)
                            <section aria-labelledby="notes-title">
                                <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                                    <div class="divide-y divide-gray-200">
                                        <div class="px-4 py-5 sm:px-6">
                                            <h2 id="notes-title" class="text-lg font-medium text-gray-900">
                                                Stereochemical
                                                Variants
                                            </h2>
                                        </div>
                                        <div class="px-4 pb-5 sm:px-6">
                                            <div
                                                class="mx-auto grid mt-6 gap-5 lg:max-w-none md:grid-cols-3 lg:grid-cols-2">
                                                @foreach ($molecule->variants as $variant)
                                                <livewire:molecule-card :molecule="$variant" lazy />
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            @endif

                            @if ($molecule->parent_id != null)
                            <section aria-labelledby="notes-title">
                                <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                                    <div class="divide-y divide-gray-200">
                                        <div class="px-4 py-5 sm:px-6">
                                            <h2 id="notes-title" class="text-lg font-medium text-gray-900">Parent
                                                (Without
                                                stereo definitions)
                                            </h2>
                                        </div>
                                        <div class="px-4 pb-5 sm:px-6">
                                            <div
                                                class="mx-auto grid mt-6 gap-5 lg:max-w-none md:grid-cols-3 lg:grid-cols-2">
                                                <div class="rounded-lg hover:shadow-lg shadow border">
                                                    <livewire:molecule-card :molecule="$molecule->parent" lazy />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            @endif

                            @if ($molecule->properties)
                            <section aria-labelledby="notes-title">
                                <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                                    <div class="divide-y divide-gray-200">
                                        <div class="px-4 py-5 sm:px-6">
                                            <h2 id="notes-title" class="text-lg font-medium text-gray-900">
                                                Molecular
                                                Properties
                                            </h2>
                                        </div>
                                        <div class="px-4 py-6 sm:px-6">
                                            <div>
                                                <ul role="list" class="px-0">
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Mol. Formula :
                                                            {{ $molecule->properties->molecular_formula }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">
                                                            Mol. Weight
                                                            <span x-data="{ tooltip: false }"
                                                                x-on:mouseover="tooltip = true"
                                                                x-on:mouseleave="tooltip = false"
                                                                class="h-5 w-5 cursor-pointer inline">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5 inline" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                <div x-show="tooltip"
                                                                    class="text-sm text-white absolute bg-green-400 rounded-lg p-2 transform -translate-y-8 translate-x-8">
                                                                    Exact Isotopic Mass is calculated using RDKit -
                                                                    <a
                                                                        href="https://www.rdkit.org/docs/source/rdkit.Chem.Descriptors.html">https://www.rdkit.org/docs/source/rdkit.Chem.Descriptors.html</a>
                                                                </div>
                                                            </span>{{ $molecule->properties->exact_molecular_weight }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Total
                                                            atom number :
                                                            {{ $molecule->properties->total_atom_count }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Heavy
                                                            atom number :
                                                            {{ $molecule->properties->heavy_atom_count }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Aromatic Ring
                                                            Count :
                                                            {{ $molecule->properties->aromatic_rings_count }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Rotatable Bond
                                                            count :
                                                            {{ $molecule->properties->rotatable_bond_count }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Minimal number of
                                                            rings
                                                            :
                                                            {{ $molecule->properties->number_of_minimal_rings }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Formal Charge :
                                                            {{ $molecule->properties->total_atom_count }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Contains Sugar :
                                                            {{ $molecule->properties->contains_sugar ? 'True' : 'False' }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Contains Ring
                                                            Sugars :
                                                            {{ $molecule->properties->contains_ring_sugars ? 'True' : 'False' }}</span>
                                                    </li>
                                                    <li class="py-2 flex md:py-0"><span
                                                            class="ml-3 text-base text-gray-500">Contains Linear
                                                            Sugars
                                                            :
                                                            {{ $molecule->properties->contains_linear_sugars ? 'True' : 'False' }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section aria-labelledby="notes-title">
                                <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg mb-10">
                                    <div class="divide-y divide-gray-200">
                                        <div class="px-4 py-5 sm:px-6">
                                            <h2 id="notes-title" class="text-lg font-medium text-gray-900">
                                                Molecular
                                                Descriptors
                                            </h2>
                                        </div>
                                        <div class="px-4 py-6 sm:px-6">
                                            <ul role="list" class="">
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">NP-likeness scores :
                                                        {{ $molecule->properties->np_likeness }}</span></li>
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">Alogp
                                                        :
                                                        {{ $molecule->properties->alogp }}</span></li>
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">TopoPSA :
                                                        {{ $molecule->properties->topological_polar_surface_area }}</span>
                                                </li>
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">Fsp3
                                                        :
                                                        {{ $molecule->properties->fractioncsp3 }}</span></li>
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">Hydrogen
                                                        Bond Acceptor Count
                                                        :
                                                        {{ $molecule->properties->hydrogen_bond_acceptors }}</span>
                                                </li>
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">Hydrogen
                                                        Bond Donor Count :
                                                        {{ $molecule->properties->hydrogen_bond_donors }}</span>
                                                </li>
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">Lipinski
                                                        Hydrogen Bond
                                                        Acceptor Count :
                                                        {{ $molecule->properties->hydrogen_bond_acceptors_lipinski }}</span>
                                                </li>
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">Lipinski
                                                        Hydrogen Bond Donor
                                                        Count :
                                                        {{ $molecule->properties->hydrogen_bond_donors_lipinski }}</span>
                                                </li>
                                                <li class="py-2 flex md:py-0"><span
                                                        class="ml-3 text-base text-gray-500">Lipinski
                                                        RO5 Violations :
                                                        {{ $molecule->properties->lipinski_rule_of_five_violations }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            @endif
                        </section>
                        <section class="space-y-6 lg:col-span-1 lg:col-start-3 order-1 lg:order-2">
                            <div class="border aspect-h-2 aspect-w-3 overflow-hidden rounded-lg bg-white mb-2 mx-2">
                                <livewire:molecule-depict2d :height="300" :molecule="$molecule" :smiles="$molecule->canonical_smiles"
                                    :name="$molecule->name" :identifier="$molecule->identifier" :options="true" lazy="on-load">
                            </div>
                            <div class="mx-2">
                                <livewire:molecule-depict3d :height="300" :molecule="$molecule" :smiles="$molecule->canonical_smiles"
                                    lazy="on-load">
                            </div>
                            <dl class="mt-5 flex w-full mx-2">
                                <div class="md:text-left">
                                    <dd class="mt-1"><a
                                            class="text-base font-semibold text-text-dark hover:text-slate-600"
                                            href="/dashboard/reports/create?compound_id={{ $molecule->identifier }}&type=report"
                                            target="_blank">Report
                                            this compound <span aria-hidden="true">→</span></a></dd>
                                    <dd class="mt-1"><a
                                            class="text-base font-semibold text-text-dark hover:text-slate-600"
                                            href="/dashboard/reports/create?compound_id={{ $molecule->identifier }}&type=change"
                                            target="_blank">Request
                                            changes to this page <span aria-hidden="true">→</span></a></dd>
                                </div>
                            </dl>
                            <div class="mx-2">
                                <livewire:molecule-history-timeline :mol="$molecule" lazy="on-load" />
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>