    <header class="absolute inset-x-0 @if(config('app.env') !== 'production') top-14 @else top-0 @endif" x-data="{ open: false }">
        <nav class="flex items-center justify-between p-6 lg:px-8 max-w-4xl lg:max-w-7xl mx-auto" aria-label="Global">
            <div class="flex lg:flex-1">
                <div class="-m-1.5 p-1.5">
                    <x-authentication-card-logo />
                </div>
            </div>
            <div class="flex lg:hidden">
                <button type="button" @click="open = true"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-12">
                <nav class="flex justify-center">
                    <ul class="flex flex-wrap items-center font-medium text-sm">
                        <li class="p-4 lg:px-8">
                            <a class="text-slate-800 hover:text-slate-900" href="/search">Search</a>
                        </li>
                        <li class="p-4 lg:px-8">
                            <a class="text-slate-800 hover:text-slate-900" href="/collections">Collections</a>
                        </li>
                        <li class="p-4 lg:px-8 relative flex items-center space-x-1" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <a class="text-slate-800 hover:text-slate-900" href="#0" :aria-expanded="open" aria-expanded="false">Docs</a>
                            <button class="shrink-0 p-1" :aria-expanded="open" @click.prevent="open = !open" aria-expanded="false">
                                <span class="sr-only">Docs</span>
                                <svg class="w-3 h-3 fill-slate-500" xmlns="http://www.w3.org/2000/svg" width="12" height="12">
                                    <path d="M10 2.586 11.414 4 6 9.414.586 4 2 2.586l4 4z"></path>
                                </svg>
                            </button>
                            <!-- 2nd level menu -->
                            <ul class="z-50 origin-top-right absolute top-full left-1/2 -translate-x-1/2 min-w-[240px] bg-white border border-slate-200 p-2 rounded-lg shadow-xl [&amp;[x-cloak]]:hidden" x-show="open" x-transition:enter="transition ease-out duration-200 transform" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-out duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @focusout="await $nextTick();!$el.contains($focus.focused()) &amp;&amp; (open = false)" style="display: none;">
                                <li>
                                    <a class="text-slate-800 hover:bg-slate-50 flex items-center p-2" href="https://steinbeck-lab.github.io/coconut/introduction.html" target="_blank">
                                        <div class="flex items-center justify-center bg-white rounded shadow-sm h-7 w-7 shrink-0 mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                        </div>
                                        <span class="whitespace-nowrap">Documentation</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="text-slate-800 hover:bg-slate-50 flex items-center p-2" href="/dashboard/collections/create" target="_blank">
                                        <div class="flex items-center justify-center bg-white rounded shadow-sm h-7 w-7 shrink-0 mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                              </svg>
                                        </div>
                                        <span class="whitespace-nowrap">Submission</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="text-slate-800 hover:bg-slate-50 flex items-center p-2" href="https://coconut.naturalproducts.net/api-documentation" target="_blank">
                                        <div class="flex items-center justify-center bg-white rounded shadow-sm h-7 w-7 shrink-0 mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75 16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                                            </svg>
                                        </div>
                                        <span class="whitespace-nowrap">REST API</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="text-slate-800 hover:bg-slate-50 flex items-center p-2" href="https://github.com/Steinbeck-Lab/coconut" target="_blank">
                                        <div class="flex items-center justify-center bg-white rounded shadow-sm h-7 w-7 shrink-0 mr-3">
                                            <svg viewBox="0 0 24 24" aria-hidden="true" class="size-6 fill-slate-900"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.463 2 11.97c0 4.404 2.865 8.14 6.839 9.458.5.092.682-.216.682-.48 0-.236-.008-.864-.013-1.695-2.782.602-3.369-1.337-3.369-1.337-.454-1.151-1.11-1.458-1.11-1.458-.908-.618.069-.606.069-.606 1.003.07 1.531 1.027 1.531 1.027.892 1.524 2.341 1.084 2.91.828.092-.643.35-1.083.636-1.332-2.22-.251-4.555-1.107-4.555-4.927 0-1.088.39-1.979 1.029-2.675-.103-.252-.446-1.266.098-2.638 0 0 .84-.268 2.75 1.022A9.607 9.607 0 0 1 12 6.82c.85.004 1.705.114 2.504.336 1.909-1.29 2.747-1.022 2.747-1.022.546 1.372.202 2.386.1 2.638.64.696 1.028 1.587 1.028 2.675 0 3.83-2.339 4.673-4.566 4.92.359.307.678.915.678 1.846 0 1.332-.012 2.407-.012 2.734 0 .267.18.577.688.48 3.97-1.32 6.833-5.054 6.833-9.458C22 6.463 17.522 2 12 2Z"></path></svg>
                                        </div>
                                        <span class="whitespace-nowrap">Codebase</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="text-slate-800 hover:bg-slate-50 flex items-center p-2" href="/stats" target="_blank">
                                        <div class="flex items-center justify-center bg-white rounded shadow-sm h-7 w-7 shrink-0 mr-3">
                                        <x-heroicon-o-chart-pie />
                                        </div>
                                        <span class="whitespace-nowrap">Statistics</span>
                                    </a>
                                </li>
                                <li>
                                    <a target="_blank" class="text-slate-800 hover:bg-slate-50 flex items-center p-2" href="https://kuma.nfdi4chem.de/status/coconut" target="_blank">
                                        <div class="flex items-center justify-center bg-white rounded shadow-sm h-7 w-7 shrink-0 mr-3">
                                        <x-heroicon-o-bars-arrow-up />
                                        </div>
                                        <span class="whitespace-nowrap">Service health</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="p-4 lg:px-8">
                            <a class="text-slate-800 hover:text-slate-900" href="/about">About us</a>
                        </li>
                        <li class="p-4 lg:px-8">
                            <a class="text-slate-800 hover:text-slate-900" href="/download">Download</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                @if (!auth()->user())
                    <a href="/login" class="text-sm font-semibold leading-6 text-gray-900">Log in <span
                            aria-hidden="true">&rarr;</span></a>
                @endif
                @if (auth()->user())
                    <a href="/dashboard" class="text-sm font-semibold leading-6 text-gray-900">Dashboard&emsp;<span
                            aria-hidden="true">&rarr;</span></a>
                @endif
            </div>
        </nav>
        <div class="lg:hidden" role="dialog" aria-modal="true" x-show="open">
            <div class="fixed inset-0 z-50" @click="open = false"></div>
            <div
                class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
                <div class="flex items-center justify-between">
                    <div class="-m-1.5 p-1.5">
                        <x-authentication-card-logo />
                    </div>
                    <button type="button" @click="open = false" class="-m-2.5 rounded-md p-2.5 text-gray-700">
                        <span class="sr-only">Close menu</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-6 divide-y divide-gray-500/10">
                        <div class="space-y-2 py-6">
                            <a href="/search"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Search</a>
                            <a href="/collections"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Collections</a>
                            <a href="https://steinbeck-lab.github.io/coconut/introduction.html"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Documentation</a>
                            <a href="/api-documentation"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">REST API</a>
                            <a href="https://github.com/Steinbeck-Lab/coconut"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Codebase</a>
                            <a href="/about"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">About
                                us</a>
                            <a href="/download"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Download</a>
                            <a href="/stats"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Statistics</a>
                            <a target="_blank" href="https://kuma.nfdi4chem.de/status/coconut"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Service health</a>
                        </div>
                        <div class="py-6">
                            @if (!auth()->user())
                                <a href="/login"
                                    class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Log
                                    in</a>
                            @endif
                            @if (auth()->user())
                                <a href="/dashboard"
                                    class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Dashboard</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <livewire:banner-component />
    </header>
