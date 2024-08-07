<div x-data="{ query: '' }">
    <div class="relative isolate -z-10">
        <svg class="absolute inset-x-0 -top-52 -z-10 h-[64rem] w-full stroke-gray-200 [mask-image:radial-gradient(32rem_32rem_at_center,white,transparent)]"
            aria-hidden="true">
            <defs>
                <pattern id="1f932ae7-37de-4c0a-a8b0-a6e3b4d44b84" width="200" height="200" x="50%" y="-1"
                    patternUnits="userSpaceOnUse">
                    <path d="M.5 200V.5H200" fill="none" />
                </pattern>
            </defs>
            <svg x="50%" y="-1" class="overflow-visible fill-gray-50">
                <path d="M-200 0h201v201h-201Z M600 0h201v201h-201Z M-400 600h201v201h-201Z M200 800h201v201h-201Z"
                    stroke-width="0" />
            </svg>
            <rect width="100%" height="100%" stroke-width="0" fill="url(#1f932ae7-37de-4c0a-a8b0-a6e3b4d44b84)" />
        </svg>
    </div>
    <div class="relative mx-auto mt-32 grid w-full max-w-4xl lg:max-w-7xl grid-cols-1 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto w-full py-1 px-4 sm:px-6 sm:py-20 lg:px-8 mb-12">
            <div class="text-center max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold tracking-tight text-primary-dark sm:text-4xl"><span
                        class="block xl:inline">COCONUT: </span><span class="block text-secondary-dark xl:inline">the
                        COlleCtion of Open NatUral producTs</span></h1>
                <p class="my-6 mx-auto text-md leading-6 text-text-light"> An aggregated dataset of elucidated and
                    predicted NPs
                    collected from open sources and a web interface to browse, search and easily and quickly download
                    NPs. </p>

                <div class="mt-3">
                    <div class="bg-white">
                        <div class="flex h-16 flex-shrink-0 rounded-md">
                            <div
                                class="flex flex-1 justify-between border-b-4 border border-gray-400 rounded-md px-4 md:px-0">
                                <div class="flex flex-1">
                                    <div class="flex w-full md:ml-0">
                                        <label for="search-field" class="sr-only">Find natural products</label>
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
                                            <input
                                                @keyup.enter="window.location.href = '/search?q=' + encodeURIComponent(query)"
                                                x-model="query" id="query"
                                                class="rounded-md h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-900 placeholder-gray-500 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:block"
                                                placeholder="Search compound name, SMILES, InChI, InChI Key"
                                                type="search" autofocus>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center mx-3">
                                    <div>
                                        <button @click="window.location.href = '/search?q=' + encodeURIComponent(query)"
                                            class="rounded-md bg-secondary-dark px-3.5 py-1.5 text-base font-semibold leading-7 text-white shadow-sm hover:bg-secondary-light focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"><svg
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
                        <p><small class="float-left">Try: <a class="underline" href="/search?q=caffeine">Caffeine</a>,
                                <a class="underline" href="/compounds/CNP0329459">CNP0329459</a></small>

                            <span
                                class="float-right  text-sm flex items-baseline gap-x-2 text-[0.8125rem]/6 text-gray-500">
                                Report bugs: <a
                                    class="group relative isolate flex items-center rounded-lg px-2 py-0.5 text-[0.8125rem]/6 font-medium text-dark/30 transition-colors hover:text-sky-900 gap-x-2"
                                    target="_blank" href="https://github.com/Steinbeck-Lab/coconut/issues"><span
                                        class="absolute inset-0 -z-10 scale-75 rounded-lg bg-white/5 opacity-0 transition group-hover:scale-100 group-hover:opacity-100"></span><svg
                                        viewBox="0 0 16 16" aria-hidden="true" fill="currentColor"
                                        class="flex-none h-4 w-4">
                                        <path
                                            d="M8 .198a8 8 0 0 0-8 8 7.999 7.999 0 0 0 5.47 7.59c.4.076.547-.172.547-.384 0-.19-.007-.694-.01-1.36-2.226.482-2.695-1.074-2.695-1.074-.364-.923-.89-1.17-.89-1.17-.725-.496.056-.486.056-.486.803.056 1.225.824 1.225.824.714 1.224 1.873.87 2.33.666.072-.518.278-.87.507-1.07-1.777-.2-3.644-.888-3.644-3.954 0-.873.31-1.586.823-2.146-.09-.202-.36-1.016.07-2.118 0 0 .67-.214 2.2.82a7.67 7.67 0 0 1 2-.27 7.67 7.67 0 0 1 2 .27c1.52-1.034 2.19-.82 2.19-.82.43 1.102.16 1.916.08 2.118.51.56.82 1.273.82 2.146 0 3.074-1.87 3.75-3.65 3.947.28.24.54.73.54 1.48 0 1.07-.01 1.93-.01 2.19 0 .21.14.46.55.38A7.972 7.972 0 0 0 16 8.199a8 8 0 0 0-8-8Z">
                                        </path>
                                    </svg><span class="self-baseline">Issue Tracker</span></a>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="mt-16 flex items-center gap-x-6 justify-center w-full">
                    <div>
                        <livewire:structure-editor :mode="'button'" lazy="on-load" />
                    </div>
                    <a href="/search"
                        class="border bg-gray-50 justify-center items-center text-center rounded-md text-gray-900 mr-1 py-3 px-4 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-12 h-12 mx-auto">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                        </svg>
                        <small class="text-base font-semibold leading-7">Browse Data</small>
                        </button>
                    </a>
                    <a href="/admin/collections/create"
                        class="border bg-gray-50 justify-center items-center text-center rounded-md text-gray-900 mr-1 py-3 px-4 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-12 h-12 mx-auto">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12" />
                        </svg>
                        <small class="text-base font-semibold leading-7">Submit Data</small>
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-gray-900">
        <div class="mx-auto max-w-7xl">
            <div class="grid grid-cols-1 gap-px bg-white/5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Molecules Section -->
                <a href="/search" class="bg-gray-900 py-6 px-8 sm:px-6 lg:px-8">
                    <p class="text-sm font-medium leading-6 text-gray-400">
                        <svg fill="currentColor" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink" class="w-5 h-5 inline"
                            viewBox="0 0 399.998 399.997" xml:space="preserve">
                            <g>
                                <g>
                                    <path d="M292.41,236.617l-42.814-27.769c5.495-15.665,4.255-33.162-3.707-48.011l35.117-31.373
   c19.292,12.035,45.001,9.686,61.771-7.085c19.521-19.52,19.521-51.171,0-70.692c-19.522-19.521-51.175-19.521-70.694,0
   c-15.378,15.378-18.632,38.274-9.788,56.848l-35.121,31.378c-16.812-11.635-38.258-13.669-56.688-6.078l-40.5-55.733
   c14.528-19.074,13.095-46.421-4.331-63.849c-19.004-19.004-49.816-19.004-68.821,0c-19.005,19.005-19.005,49.818,0,68.822
   c13.646,13.646,33.374,17.491,50.451,11.545l40.505,55.738c-20.002,23.461-18.936,58.729,3.242,80.906
   c0.426,0.426,0.864,0.825,1.303,1.237l-39.242,68.874c-16.31-3.857-34.179,0.564-46.899,13.286
   c-19.521,19.522-19.521,51.175,0,70.694c19.521,19.521,51.173,19.521,70.693,0c19.317-19.315,19.508-50.503,0.593-70.069
   l39.239-68.867c19.705,5.658,41.737,0.978,57.573-14.033l42.855,27.79c-2.736,12.706,0.821,26.498,10.696,36.372
   c15.469,15.469,40.544,15.469,56.012,0c15.468-15.466,15.468-40.543,0-56.011C329.831,226.518,307.908,225.209,292.41,236.617z
   M83.129,338.906c-0.951,1.078-1.846,2.096-2.724,2.973c-1.094,1.093-2.589,2.425-4.444,2.998
   c-2.33,0.719-4.711,0.086-6.536-1.739c-4.772-4.771-2.947-13.799,4.246-20.989c7.195-7.195,16.219-9.021,20.993-4.247
   c1.824,1.822,2.457,4.205,1.737,6.536c-0.572,1.855-1.904,3.354-2.997,4.444c-0.878,0.876-1.896,1.771-2.975,2.722
   c-1.245,1.096-2.535,2.229-3.805,3.497C85.355,336.37,84.224,337.66,83.129,338.906z M279.56,59.17
   c7.193-7.193,16.219-9.02,20.991-4.247c1.823,1.823,2.458,4.205,1.737,6.537c-0.572,1.856-1.905,3.354-2.997,4.446
   c-0.876,0.875-1.894,1.77-2.974,2.72c-1.246,1.097-2.534,2.229-3.805,3.498c-1.271,1.271-2.403,2.562-3.5,3.808
   c-0.948,1.076-1.846,2.097-2.72,2.973c-1.093,1.093-2.591,2.425-4.446,2.998c-2.332,0.719-4.712,0.086-6.536-1.739
   C270.541,75.391,272.366,66.362,279.56,59.17z M73.322,37.854c-0.928,1.05-1.799,2.042-2.648,2.895
   c-1.063,1.063-2.521,2.358-4.329,2.919c-2.269,0.698-4.587,0.083-6.364-1.691c-4.646-4.647-2.866-13.436,4.138-20.438
   c7.003-7.004,15.788-8.782,20.436-4.135c1.776,1.776,2.395,4.095,1.692,6.363c-0.561,1.807-1.854,3.265-2.918,4.326
   c-0.854,0.854-1.846,1.727-2.896,2.648c-1.213,1.066-2.469,2.17-3.704,3.406C75.492,35.384,74.387,36.642,73.322,37.854z
   M159.967,155.76c8.593-8.594,19.371-10.774,25.073-5.073c2.18,2.181,2.937,5.024,2.078,7.81
   c-0.688,2.218-2.277,4.005-3.583,5.312c-1.047,1.047-2.265,2.112-3.553,3.248c-1.486,1.311-3.026,2.662-4.544,4.179
   c-1.518,1.519-2.87,3.058-4.178,4.547c-1.136,1.287-2.205,2.505-3.251,3.55c-1.306,1.31-3.093,2.896-5.311,3.582
   c-2.784,0.859-5.628,0.104-7.811-2.077C149.189,175.132,151.374,164.354,159.967,155.76z M299.11,262.103
   c-0.868,0.866-2.056,1.923-3.524,2.376c-1.846,0.569-3.729,0.068-5.178-1.377c-3.783-3.781-2.338-10.933,3.365-16.633
   c5.697-5.7,12.849-7.146,16.632-3.362c1.443,1.443,1.945,3.33,1.376,5.179c-0.453,1.471-1.51,2.656-2.375,3.521
   c-0.694,0.692-1.5,1.402-2.355,2.155c-0.984,0.866-2.008,1.766-3.013,2.771c-1.007,1.006-1.907,2.026-2.771,3.016
   C300.512,260.604,299.802,261.409,299.11,262.103z" />
                                </g>
                            </g>
                        </svg>
                        Total Molecules
                    </p>
                    <p class="mt-2 flex items-baseline gap-x-2">
                        <span class="text-4xl font-semibold tracking-tight text-white number"
                            data-value="{{ $totalMolecules }}">
                            {{ $totalMolecules }}
                        </span>
                    </p>
                </a>
                <!-- Total Collections Section -->
                <a href="/collections" class="bg-gray-900 px-8 py-6 sm:px-6 lg:px-8">
                    <p class="text-sm font-medium leading-6 text-gray-400">
                        <svg class="w-5 h-5 inline" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor">
                            <path
                                d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-13z" />
                        </svg>
                        Total Collections
                    </p>
                    <p class="mt-2 flex items-baseline gap-x-2">
                        <span class="text-4xl font-semibold tracking-tight text-white number"
                            data-value="{{ $totalCollections }}">
                            {{ $totalCollections }}
                        </span>
                    </p>
                </a>
                <!-- Unique Organisms Section -->
                <div class="bg-gray-900 px-8 py-6 sm:px-6 lg:px-8">
                    <p class="text-sm font-medium leading-6 text-gray-400">
                        <svg fill="currentColor" class="w-5 h-5 inline" version="1.1" id="Layer_1"
                            xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                            viewBox="0 0 512 512" xml:space="preserve">
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
                                    <path d="M453.823,290.909c-6.435,0-11.636,5.213-11.636,11.636c0,25.67-20.876,46.545-46.545,46.545
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
                        Unique Organisms
                    </p>
                    <p class="mt-2 flex items-baseline gap-x-2">
                        <span class="text-4xl font-semibold tracking-tight text-white number"
                            data-value="{{ $uniqueOrganisms }}">
                            {{ $uniqueOrganisms }}
                        </span>
                    </p>
                </div>
                <!-- Citations Mapped Section -->
                <div class="bg-gray-900 px-8 py-6 sm:px-6 lg:px-8">
                    <p class="text-sm font-medium leading-6 text-gray-400">
                        <svg class="w-5 h-5 inline" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M6 1h6v7a.5.5 0 0 1-.757.429L9 7.083 6.757 8.43A.5.5 0 0 1 6 8V1z" />
                            <path
                                d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z" />
                            <path
                                d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z" />
                        </svg>
                        Citations Mapped
                    </p>
                    <p class="mt-2 flex items-baseline gap-x-2">
                        <span class="text-4xl font-semibold tracking-tight text-white number"
                            data-value="{{ $citationsMapped }}">
                            {{ $citationsMapped }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <livewire:recent-molecules lazy />
    <livewire:compound-classes lazy="on-load" />
    <livewire:data-sources lazy="on-load" />
    <livewire:faqs lazy="on-load" />

    <div class="bg-gray-900 border-y">
        <div
            class="mx-auto max-w-4xl py-16 px-4 sm:px-6 sm:py-24 lg:flex lg:max-w-7xl lg:items-center lg:justify-between lg:px-8">
            <div>
                <h2 class="text-4xl font-bold tracking-tight text-white sm:text-4xl"><span class="block">Want to
                        contribute?</span><span
                        class="-mb-1 block bg-gradient-to-r from-secondary-dark to-secondary-light bg-clip-text pb-1 text-transparent">Get
                        in touch or create an account.</span></h2>
            </div>
            <div class="mt-6 space-y-4 sm:flex sm:space-y-0 sm:space-x-5"><a href="https://cheminf.uni-jena.de"
                    target="_blank"
                    class="cursor-pointer flex items-center justify-center rounded-md border border-transparent bg-teal-50 px-4 py-3 text-base font-medium text-teal-800 shadow-sm hover:bg-teal-100">Contact
                    Us</a>
            </div>
        </div>
    </div>
</div>
