<div class="mx-auto max-w-3xl lg:max-w-5xl">
    <div class="py-10 bg-white mt-32 rounded-lg shadow-md">
        <div
            class="mx-auto max-w-3xl px-4 sm:px-6 md:flex md:items-center md:justify-between md:space-x-5 lg:max-w-7xl lg:px-8">
            <div class="flex items-center space-x-5">
                <div>
                    <p class="text-secondary-dark text-lg my-0">{{ $molecule->identifier }}</p>
                    <h2
                        class="text-2xl break-all font-bold leading-7 break-words text-gray-900 sm:text-3xl sm:tracking-tight">
                        {{ $molecule->name }}
                    </h2>
                    <p class="text-sm font-medium text-gray-500">Created on <time
                            datetime="{{ $molecule->created_at }}">{{ $molecule->created_at }}</time> &middot; Last
                        update on <time datetime="{{ $molecule->updated_at }}">{{ $molecule->updated_at }}</time></p>
                </div>
            </div>
        </div>
        @if($molecule->properties)
        <div class="border-b mt-8 border-b-gray-900/10 lg:border-t lg:border-t-gray-900/5">
            <dl class="mx-auto grid max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:px-2 xl:px-0">
                <div
                    class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 ">
                    <dt class="font-medium text-gray-500"> NPLikeness
                        <div class="tooltip"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="currentColor" aria-hidden="true" class="h-5 w-5 -mt-1 inline cursor-pointer">
                                <path fill-rule="evenodd"
                                    d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 11-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="tooltiptext">NP Likeness Score: The likelihood of the compound to be a natural
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
                                <span class="ml-1 text-sm font-bold">{{ $molecule->properties->np_likeness }}</span>
                            </span></div>
                    </div>

                </div>
                <div
                    class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                    <div>
                        <dt class="font-medium text-gray-500"> Annotation Level</dt>
                        <div class="flex items-center">
                            <div class="flex items-center">
                                @if ($molecule->annotation_level > 0)
                                    @foreach (range(0, $molecule->annotation_level) as $i)
                                        <svg :key="index" v-for="index in molecule.annotation_level"
                                            class="inline text-yellow-400 h-5 w-5 flex-shrink-0" x-state:on="Active"
                                            x-state:off="Inactive"
                                            x-state-description='Active: "text-yellow-400", Inactive: "text-gray-200"'
                                            x-description="Heroicon name: mini/star" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    @endforeach
                                @endif
                                @foreach (range(1, 5 - $molecule->annotation_level) as $j)
                                    <svg :key="index" v-for="index in 5 - molecule.annotation_level"
                                        class="inline text-gray-200 h-5 w-5 flex-shrink-0"
                                        x-state-description='undefined: "text-yellow-400", undefined: "text-gray-200"'
                                        x-description="Heroicon name: mini/star" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 lg:border-l">
                    <div>
                        <dt class="font-medium text-gray-500 text-gray-500"> Mol. Weight </dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $molecule->properties->molecular_weight }}</dd>
                    </div>
                </div>
                <div
                    class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                    <div>
                        <dt class="font-medium text-gray-500 text-gray-500"> Mol. Formula </dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $molecule->properties->molecular_formula }}</dd>
                    </div>
                </div>
            </dl>
        </div>
        @endif

        <div
            class="mx-auto mt-8 grid max-w-3xl grid-cols-1 gap-6 sm:px-6 lg:max-w-7xl lg:grid-flow-col-dense lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2 lg:col-start-1">
                <section>
                    <div class="bg-white border shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6">
                            <h2 id="applicant-information-title" class="text-lg font-medium leading-6 text-gray-900">
                                Representations</h2>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">Molecular details</p>
                        </div>
                        <div class="border-t border-gray-200">
                            <div class="no-scrollbar px-4 lg:px-8 min-w-0">
                                <article>
                                    <div class="">
                                        <section id="representations" class="my-4">
                                            <div class="group/item -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                <dt
                                                    class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                    COCONUT id
                                                </dt>
                                                <div class="mt-1 break-all text-sm text-gray-900">
                                                    {{ $molecule->identifier }}</div>
                                            </div>
                                            <div class="group/item -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                <dt
                                                    class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                    Name
                                                </dt>
                                                <div class="mt-1 break-all text-sm text-gray-900">
                                                    {{ $molecule->name }}
                                                </div>
                                            </div>
                                            <div class="group/item -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                <dt
                                                    class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                    IUPAC name
                                                </dt>
                                                <div class="mt-1 break-all text-sm text-gray-900">
                                                    {{ $molecule->iupac_name }}
                                                </div>
                                            </div>
                                            <div class="group/item -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                <dt
                                                    class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                    InChI
                                                </dt>
                                                <div class="mt-1 break-all text-sm text-gray-900">
                                                    {{ $molecule->standard_inchi }}
                                                </div>
                                            </div>
                                            <div class="group/item -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                <dt
                                                    class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                    InChIKey
                                                </dt>
                                                <div class="mt-1 break-all text-sm text-gray-900">
                                                    {{ $molecule->standard_inchi_key }}</div>
                                            </div>
                                            <div class="group/item -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                <dt
                                                    class="text-sm font-medium text-gray-500 sm:flex sm:justify-between">
                                                    Canonical SMILES (CDK)
                                                </dt>
                                                <div class="mt-1 break-all text-sm text-gray-900">
                                                    {{ $molecule->canonical_smiles }}
                                                </div>
                                            </div>
                                            @if($molecule->properties)
                                            <div class="group/item -ml-4 rounded-xl p-4 hover:bg-slate-100">
                                                <div class="sm:flex sm:justify-between">
                                                    <div class="text-sm font-medium text-gray-500"> Murcko Framework
                                                    </div>
                                                </div>
                                                <div class="mt-1 break-all text-sm text-gray-900">
                                                    {{ $molecule->properties->murko_framework }}
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
                <section aria-labelledby="notes-title">
                    <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                        <div class="divide-y divide-gray-200">
                            <div class="px-4 py-5 sm:px-6">
                                <h2 id="notes-title" class="text-lg font-medium text-gray-900">Synonyms</h2>
                            </div>
                            <div class="px-4 py-6 sm:px-6">
                                <div class="not-prose flex gap-3">
                                    @if ($molecule->synonyms && count($molecule->synonyms) > 0)
                                        <ul role="list" class="mt-2 leading-8">
                                            @foreach ($molecule->synonyms as $synonym)
                                                @if ($synonym != '')
                                                    <li class="inline"><a
                                                            class="text-sm relative mr-2 inline-flex items-center rounded-md border border-gray-300 px-3 py-0.5"
                                                            target="_blank">
                                                            {{ $synonym }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    @else
                                        <span> No synonyms or alternative names were found for this compound </span>
                                    @endif
                                </div>
                                <div class="gap-3 mt-4">
                                    @if ($molecule->cas && count($molecule->cas) > 0)
                                        <h2 id="notes-title" class="text-md font-medium text-gray-900">CAS</h2>
                                        <ul role="list" class="mt-2 leading-8">
                                            @foreach ($molecule->cas as $cas)
                                                @if ($cas != '')
                                                    <li class="inline"><a
                                                            class="text-sm relative mr-2 inline-flex items-center rounded-md border border-gray-300 px-3 py-0.5"
                                                            target="_blank">
                                                            {{ $cas }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @if($molecule->properties)
                <section aria-labelledby="notes-title">
                    <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                        <div class="divide-y divide-gray-200">
                            <div class="px-4 py-5 sm:px-6">
                                <h2 id="notes-title" class="text-lg font-medium text-gray-900">Molecular Properties
                                </h2>
                            </div>
                            <div class="px-4 py-6 sm:px-6">
                                <div>
                                    <ul role="list" class="px-0">
                                        <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Total
                                                atom number : {{ $molecule->properties->total_atom_count }}</span></li>
                                        <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Heavy
                                                atom number :
                                                {{ $molecule->properties->heavy_atom_count }}</span></li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500">Aromatic Ring Count :
                                                {{ $molecule->properties->aromatic_rings_count }}</span></li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500">Rotatable Bond count :
                                                {{ $molecule->properties->rotatable_bond_count }}</span></li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500">Minimal number of rings
                                                : {{ $molecule->properties->number_of_minimal_rings }}</span></li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500">Formal Charge :
                                                {{ $molecule->properties->total_atom_count }}</span></li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500">Contains Sugar :
                                                {{ $molecule->properties->contains_sugar }}</span></li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500">Contains Ring Sugars :
                                                {{ $molecule->properties->contains_ring_sugars }}</span></li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500">Contains Linear Sugars
                                                : {{ $molecule->properties->contains_linear_sugars }}</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section aria-labelledby="notes-title">
                    <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                        <div class="divide-y divide-gray-200">
                            <div class="px-4 py-5 sm:px-6">
                                <h2 id="notes-title" class="text-lg font-medium text-gray-900">Molecular Descriptors
                                </h2>
                            </div>
                            <div class="px-4 py-6 sm:px-6">
                                <ul role="list" class="">
                                    <li class="py-5 flex md:py-0"><span
                                            class="ml-3 text-base text-gray-500">NP-likeness scores :
                                            {{ $molecule->properties->np_likeness }}</span></li>
                                    <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Alogp :
                                            {{ $molecule->properties->alogp }}</span></li>
                                    <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">TopoPSA :
                                            {{ $molecule->properties->topological_polar_surface_area }}</span></li>
                                    <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Fsp3 :
                                            {{ $molecule->properties->total_atom_count }}</span></li>
                                    <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Hydrogen
                                            Bond Acceptor Count
                                            : {{ $molecule->properties->hydrogen_bond_acceptors }}</span></li>
                                    <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Hydrogen
                                            Bond Donor Count : {{ $molecule->properties->hydrogen_bond_donors }}</span>
                                    </li>
                                    <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Lipinski
                                            Hydrogen Bond
                                            Acceptor Count :
                                            {{ $molecule->properties->hydrogen_bond_acceptors_lipinski }}</span></li>
                                    <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Lipinski
                                            Hydrogen Bond Donor
                                            Count : {{ $molecule->properties->hydrogen_bond_donors_lipinski }}</span>
                                    </li>
                                    <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">Lipinski
                                            RO5 Violations :
                                            {{ $molecule->properties->lipinski_rule_of_five_violations }}</span></li>
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
                                <h2 id="notes-title" class="text-lg font-medium text-gray-900">References</h2>
                            </div>
                            <div class="px-4 py-6 sm:px-6">
                                <h2 id="notes-title" class="mb-2 text-lg font-medium text-gray-900">Citations</h2>
                                @if (count($molecule->citations) > 0)
                                    <div class="not-prose grid grid-cols-1 gap-6 sm:grid-cols-2">
                                        @foreach ($molecule->citations as $citation)
                                            <div class="group relative rounded-xl border border-slate-200">
                                                <div
                                                    class="absolute -inset-px rounded-xl border-2 border-transparent opacity-0 [background:linear-gradient(var(--quick-links-hover-bg,theme(colors.sky.50)),var(--quick-links-hover-bg,theme(colors.sky.50)))_padding-box,linear-gradient(to_top,theme(colors.indigo.400),theme(colors.cyan.400),theme(colors.sky.500))_border-box] group-hover:opacity-100">
                                                </div>
                                                <div class="relative overflow-hidden rounded-xl p-6"><svg
                                                        aria-hidden="true" viewBox="0 0 32 32" fill="none"
                                                        class="h-8 w-8 [--icon-foreground:theme(colors.slate.900)] [--icon-background:theme(colors.white)]">
                                                        <defs>
                                                            <radialGradient cx="0" cy="0" r="1"
                                                                gradientUnits="userSpaceOnUse" id=":R1k19n6:-gradient"
                                                                gradientTransform="matrix(0 21 -21 0 12 11)">
                                                                <stop stop-color="#0EA5E9"></stop>
                                                                <stop stop-color="#22D3EE" offset=".527"></stop>
                                                                <stop stop-color="#818CF8" offset="1"></stop>
                                                            </radialGradient>
                                                            <radialGradient cx="0" cy="0" r="1"
                                                                gradientUnits="userSpaceOnUse"
                                                                id=":R1k19n6:-gradient-dark"
                                                                gradientTransform="matrix(0 24.5 -24.5 0 16 5.5)">
                                                                <stop stop-color="#0EA5E9"></stop>
                                                                <stop stop-color="#22D3EE" offset=".527"></stop>
                                                                <stop stop-color="#818CF8" offset="1"></stop>
                                                            </radialGradient>
                                                        </defs>
                                                        <g class="">
                                                            <circle cx="12" cy="20" r="12"
                                                                fill="url(#:R1k19n6:-gradient)"></circle>
                                                            <path d="M27 12.13 19.87 5 13 11.87v14.26l14-14Z"
                                                                class="fill-[var(--icon-background)] stroke-[color:var(--icon-foreground)]"
                                                                fill-opacity="0.5" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path d="M3 3h10v22a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V3Z"
                                                                class="fill-[var(--icon-background)]"
                                                                fill-opacity="0.5">
                                                            </path>
                                                            <path
                                                                d="M3 9v16a4 4 0 0 0 4 4h2a4 4 0 0 0 4-4V9M3 9V3h10v6M3 9h10M3 15h10M3 21h10"
                                                                class="stroke-[color:var(--icon-foreground)]"
                                                                stroke-width="2" stroke-linecap="round"
                                                                stroke-linejoin="round"></path>
                                                            <path d="M29 29V19h-8.5L13 26c0 1.5-2.5 3-5 3h21Z"
                                                                fill-opacity="0.5"
                                                                class="fill-[var(--icon-background)] stroke-[color:var(--icon-foreground)]"
                                                                stroke-width="2" stroke-linecap="round"
                                                                stroke-linejoin="round"></path>
                                                        </g>
                                                        <g class="hidden">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M3 2a1 1 0 0 0-1 1v21a6 6 0 0 0 12 0V3a1 1 0 0 0-1-1H3Zm16.752 3.293a1 1 0 0 0-1.593.244l-1.045 2A1 1 0 0 0 17 8v13a1 1 0 0 0 1.71.705l7.999-8.045a1 1 0 0 0-.002-1.412l-6.955-6.955ZM26 18a1 1 0 0 0-.707.293l-10 10A1 1 0 0 0 16 30h13a1 1 0 0 0 1-1V19a1 1 0 0 0-1-1h-3ZM5 18a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H5Zm-1-5a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1Zm1-7a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2H5Z"
                                                                fill="url(#:R1k19n6:-gradient-dark)"></path>
                                                        </g>
                                                    </svg>
                                                    <h2 class="mt-2 font-bold text-base text-gray-900"><a
                                                            target="_blank"
                                                            href="https://doi.org/{{ $citation->doi }}"><span
                                                                class="absolute -inset-px rounded-xl"></span>{{ $citation->title }}</a>
                                                    </h2>
                                                    <h2 class="mt-2 font-display text-base text-slate-900"><a
                                                            target="_blank"
                                                            href="https://doi.org/{{ $citation->doi }}"><span
                                                                class="absolute -inset-px rounded-xl"></span>{{ $citation->authors }}</a>
                                                    </h2>
                                                    <h2 class="mt-2 font-display text-base text-slate-900"><a
                                                            target="_blank"
                                                            href="https://doi.org/{{ $citation->doi }}"><span
                                                                class="absolute -inset-px rounded-xl"></span>{{ $citation->doi }}</a>
                                                    </h2>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-400">No citations</p>
                                @endif


                                <h2 id="notes-title" class="mb-2 mt-4 text-lg font-medium text-gray-900">Collections
                                </h2>
                                <div class="not-prose grid grid-cols-1 gap-6 sm:grid-cols-1">
                                    @foreach ($molecule->collections as $collection)
                                        <a
                                            href="/search?type=tags&amp;q={{ $collection->title }}&amp;tagType=dataSource">
                                            <div class="group relative rounded-xl border border-slate-200">
                                                <div
                                                    class="absolute -inset-px rounded-xl border-2 border-transparent opacity-0 [background:linear-gradient(var(--quick-links-hover-bg,theme(colors.sky.50)),var(--quick-links-hover-bg,theme(colors.sky.50)))_padding-box,linear-gradient(to_top,theme(colors.indigo.400),theme(colors.cyan.400),theme(colors.sky.500))_border-box] group-hover:opacity-100">
                                                </div>
                                                <div class="relative overflow-hidden rounded-xl p-6"><svg
                                                        aria-hidden="true" viewBox="0 0 32 32" fill="none"
                                                        class="h-8 w-8 [--icon-foreground:theme(colors.slate.900)] [--icon-background:theme(colors.white)]">
                                                        <defs>
                                                            <radialGradient cx="0" cy="0" r="1"
                                                                gradientUnits="userSpaceOnUse" id=":R1k19n6:-gradient"
                                                                gradientTransform="matrix(0 21 -21 0 12 11)">
                                                                <stop stop-color="#0EA5E9"></stop>
                                                                <stop stop-color="#22D3EE" offset=".527"></stop>
                                                                <stop stop-color="#818CF8" offset="1"></stop>
                                                            </radialGradient>
                                                            <radialGradient cx="0" cy="0" r="1"
                                                                gradientUnits="userSpaceOnUse"
                                                                id=":R1k19n6:-gradient-dark"
                                                                gradientTransform="matrix(0 24.5 -24.5 0 16 5.5)">
                                                                <stop stop-color="#0EA5E9"></stop>
                                                                <stop stop-color="#22D3EE" offset=".527"></stop>
                                                                <stop stop-color="#818CF8" offset="1"></stop>
                                                            </radialGradient>
                                                        </defs>
                                                        <g class="">
                                                            <circle cx="12" cy="20" r="12"
                                                                fill="url(#:R1k19n6:-gradient)"></circle>
                                                            <path d="M27 12.13 19.87 5 13 11.87v14.26l14-14Z"
                                                                class="fill-[var(--icon-background)] stroke-[color:var(--icon-foreground)]"
                                                                fill-opacity="0.5" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path d="M3 3h10v22a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V3Z"
                                                                class="fill-[var(--icon-background)]"
                                                                fill-opacity="0.5">
                                                            </path>
                                                            <path
                                                                d="M3 9v16a4 4 0 0 0 4 4h2a4 4 0 0 0 4-4V9M3 9V3h10v6M3 9h10M3 15h10M3 21h10"
                                                                class="stroke-[color:var(--icon-foreground)]"
                                                                stroke-width="2" stroke-linecap="round"
                                                                stroke-linejoin="round"></path>
                                                            <path d="M29 29V19h-8.5L13 26c0 1.5-2.5 3-5 3h21Z"
                                                                fill-opacity="0.5"
                                                                class="fill-[var(--icon-background)] stroke-[color:var(--icon-foreground)]"
                                                                stroke-width="2" stroke-linecap="round"
                                                                stroke-linejoin="round"></path>
                                                        </g>
                                                        <g class="hidden">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M3 2a1 1 0 0 0-1 1v21a6 6 0 0 0 12 0V3a1 1 0 0 0-1-1H3Zm16.752 3.293a1 1 0 0 0-1.593.244l-1.045 2A1 1 0 0 0 17 8v13a1 1 0 0 0 1.71.705l7.999-8.045a1 1 0 0 0-.002-1.412l-6.955-6.955ZM26 18a1 1 0 0 0-.707.293l-10 10A1 1 0 0 0 16 30h13a1 1 0 0 0 1-1V19a1 1 0 0 0-1-1h-3ZM5 18a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H5Zm-1-5a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H5a1 1 0 0 1-1-1Zm1-7a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2H5Z"
                                                                fill="url(#:R1k19n6:-gradient-dark)"></path>
                                                        </g>
                                                    </svg>
                                                    <h2 class="mt-2 font-bold text-base text-gray-900"><span
                                                            class="absolute -inset-px rounded-xl"></span>{{ $collection->title }}
                                                    </h2>
                                                    <h2 class="mt-2 font-display text-base text-slate-900"><span
                                                            class="absolute -inset-px rounded-xl"></span>{{ $collection->description }}
                                                    </h2>
                                                    <h2 class="mt-2 font-display text-base text-slate-900"><span
                                                            class="absolute -inset-px rounded-xl"></span>{{ $collection->doi }}
                                                    </h2>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                @if ($molecule->properties)
                    <section aria-labelledby="notes-title">
                        <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                            <div class="divide-y divide-gray-200">
                                <div class="px-4 py-5 sm:px-6">
                                    <h2 id="notes-title" class="text-lg font-medium text-gray-900">Chemical
                                        classification
                                    </h2>
                                </div>
                                <div class="px-4 py-6 sm:px-6">
                                    <ul role="list" class="px-0">
                                        <li class="py-5 flex md:py-0"><span class="ml-3 text-base text-gray-500">
                                                <b>Super class</b>:
                                                {{ $molecule->properties && $molecule->properties['chemical_super_class'] ? $molecule->properties['chemical_super_class']['name'] : '-' }}
                                            </span>
                                        </li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500"><b>Class</b>:
                                                {{ $molecule->properties && $molecule->properties['chemical_class'] ? $molecule->properties['chemical_class']['name'] : '-' }}</span>
                                        </li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500"><b>Sub
                                                    class</b>:
                                                {{ $molecule->properties && $molecule->properties['chemical_sub_class'] ? $molecule->properties['chemical_sub_class']['name'] : '-' }}
                                            </span>
                                        </li>
                                        <li class="py-5 flex md:py-0"><span
                                                class="ml-3 text-base text-gray-500"><b>Direct
                                                    parent</b>:
                                                {{ $molecule->properties && $molecule->properties['direct_parent_classification'] ? $molecule->properties['direct_parent_classification']['name'] : '-' }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($molecule->is_parent)
                    <section aria-labelledby="notes-title">
                        <div class="bg-white shadow border sm:overflow-hidden sm:rounded-lg">
                            <div class="divide-y divide-gray-200">
                                <div class="px-4 py-5 sm:px-6">
                                    <h2 id="notes-title" class="text-lg font-medium text-gray-900">Stereochemical
                                        Variants
                                    </h2>
                                </div>
                                <div class="px-4 pb-5 sm:px-6">
                                    <div class="mx-auto grid mt-6 gap-5 lg:max-w-none md:grid-cols-3 lg:grid-cols-2">
                                        @foreach ($molecule->variants as $variant)
                                            <livewire:molecule-card :molecule="json_encode($variant)" />
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
                                    </h2>
                                </div>
                                <div class="px-4 pb-5 sm:px-6">
                                    <div class="mx-auto grid mt-6 gap-5 lg:max-w-none md:grid-cols-3 lg:grid-cols-2">
                                        <div class="rounded-lg hover:shadow-lg shadow border">
                                            <livewire:molecule-card :molecule="json_encode($molecule->parent)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

            </div>

            <section aria-labelledby="timeline-title" class="lg:col-span-1 lg:col-start-3">
                <div class="border aspect-h-2 aspect-w-3 overflow-hidden rounded-lg bg-white mb-2">
                    <livewire:molecule-depict2d :height="300" :smiles="$molecule->canonical_smiles">
                </div>
                <div class="border aspect-h-2 aspect-w-3 overflow-hidden rounded-lg mb-2">
                    <livewire:molecule-depict3d :height="300" :smiles="$molecule->canonical_smiles">
                </div>
                <div class="bg-white px-4 py-5 shadow sm:rounded-lg sm:px-6 border">
                    <h2 id="timeline-title" class="text-lg font-medium text-gray-900">Timeline</h2>

                    <div class="mt-6 flow-root">
                        <ul role="list" class="-mb-8">
                            <li>
                                <div class="relative pb-8">
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span
                                                class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                <svg class="h-5 w-5 text-white" viewBox="0 0 20 20"
                                                    fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm text-gray-500">Created at <a href="#"
                                                        class="font-medium text-gray-900"></a></p>
                                                <time
                                                    datetime="{{ $molecule->created_at }}">{{ $molecule->created_at }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>

                        </ul>
                    </div>
                    <div class="mt-6 flex flex-col justify-stretch">
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600">View
                            complete history</button>
                    </div>
                </div>

                <dl class="mt-5 flex w-full">
                    <div class="text-center md:text-left">
                        <dd class="mt-1"><a class="text-base font-semibold text-text-dark hover:text-slate-600"
                                href="http://localhost/dashboard/reports/create?compound_id={{ $molecule->identifier }}">
                                Report this compound <span aria-hidden="true">→</span></a></dd>
                        <dd class="mt-1"><a class="text-base font-semibold text-text-dark hover:text-slate-600"
                                href="https://dev.coconut.naturalproducts.net/compounds/{{ $molecule->identifier }}/update">Request
                                changes to this page <span aria-hidden="true">→</span></a></dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</div>
