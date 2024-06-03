    <header class="absolute inset-x-0 top-0 z-50" x-data="{ open: false }">
        <nav class="flex items-center justify-between p-6 lg:px-8 max-w-4xl lg:max-w-7xl mx-auto" aria-label="Global">
            <div class="flex lg:flex-1">
                <a href="/" class="-m-1.5 p-1.5">
                    <x-authentication-card-logo />
                </a>
            </div>
            <div class="flex lg:hidden">
                <button type="button" @click="open = true"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-12">
                <a href="/search" class="text-sm font-semibold leading-6 text-gray-900">Browse</a>
                <a href="/api-documentation" class="text-sm font-semibold leading-6 text-gray-900">API</a>
                <a href="/guidelines" class="text-sm font-semibold leading-6 text-gray-900">Guidelines</a>
                <a href="https://cheminf.uni-jena.de/" class="text-sm font-semibold leading-6 text-gray-900">About us</a>
                <a href="/download" class="text-sm font-semibold leading-6 text-gray-900">Download</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                @if (!auth()->user())
                    <a href="/login" class="text-sm font-semibold leading-6 text-gray-900">Log in <span aria-hidden="true">&rarr;</span></a>
                @endif
                @if (auth()->user())
                    <a href="/dashboard" class="text-sm font-semibold leading-6 text-gray-900">Dashboard&emsp;<span aria-hidden="true">&rarr;</span></a>
                @endif
            </div>
        </nav>
        <div class="lg:hidden" role="dialog" aria-modal="true" x-show="open">
            <div class="fixed inset-0 z-50" @click="open = false"></div>
            <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
                <div class="flex items-center justify-between">
                    <a href="/" class="-m-1.5 p-1.5">
                        <x-authentication-card-logo />
                    </a>
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
                            <a href="/search" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Browse</a>
                            <a href="/api-documentation" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">API</a>
                            <a href="/guidelines" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Guidelines</a>
                            <a href="https://cheminf.uni-jena.de/" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">About us</a>
                            <a href="/download" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Download</a>
                        </div>
                        <div class="py-6">
                            @if (!auth()->user())
                                <a href="/login" class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Log in</a>
                            @endif
                            @if (auth()->user())
                                <a href="/dashboard" class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Dashboard</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>