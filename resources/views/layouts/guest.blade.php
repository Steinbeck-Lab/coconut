<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Coconut') }}</title>

        <!-- Meta Tags -->
        <meta name="description" content="{{ $description ?? 'An aggregated dataset of elucidated and predicted natural products collected from open sources and a web interface to browse, search, and easily download NPs.' }}">
        <meta name="keywords" content="{{ $keywords ?? 'natural products, COCONUT, open data, molecule database' }}">
        <meta name="author" content="{{ $author ?? 'COCONUT Team' }}">
        <meta property="og:title" content="{{ $ogTitle ?? 'COCONUT: COlleCtion of Open NatUral producTs' }}">
        <meta property="og:description" content="{{ $ogDescription ?? 'An aggregated dataset of elucidated and predicted natural products collected from open sources and a web interface to browse, search, and easily download NPs.' }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $ogUrl ?? url()->current() }}">
        <meta property="og:image" content="{{ $ogImage ?? asset('img/coconut-og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:site_name" content="{{ $ogSiteName ?? config('app.name', 'Coconut') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body>
    <div class="absolute inset-0 bg-gradient-to-r from-[#FFF] to-[#BDBDBD] opacity-20 [mask-image:radial-gradient(farthest-side_at_top,white,transparent)] dark:from-[#36b49f]/30 dark:to-[#DBFF75]/30 dark:opacity-100"><svg aria-hidden="true" class="absolute inset-x-0 inset-y-[-50%] h-[200%] w-full skew-y-[-18deg] fill-black/20 stroke-black/50 mix-blend-overlay dark:fill-white/2.5 dark:stroke-white/5"><defs><pattern id=":r99:" width="72" height="56" patternUnits="userSpaceOnUse" x="-12" y="4"><path d="M.5 56V.5H72" fill="none"></path></pattern></defs><rect width="100%" height="100%" stroke-width="0" fill="url(#:r99:)"></rect><svg x="-12" y="4" class="overflow-visible"><rect stroke-width="0" width="73" height="57" x="288" y="168"></rect><rect stroke-width="0" width="73" height="57" x="144" y="56"></rect><rect stroke-width="0" width="73" height="57" x="504" y="168"></rect><rect stroke-width="0" width="73" height="57" x="720" y="336"></rect></svg></svg></div>
        <x-banner />
        <x-impersonate::banner style='light'/>
        <div class="font-sans text-gray-900 antialiased">
            <div class="bg-white">
                <livewire:header />
                <main class="isolate">
                {{ $slot }}
                </main>
                <livewire:footer />
            </div>
        </div>
        @include('components.tawk-chat')
        @livewireScripts
        @include('cookie-consent::index')
    </body>
     <script>
        document.addEventListener('DOMContentLoaded', function() {
            function animateValue(element, start, end, duration) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    element.innerText = Math.floor(progress * (end - start) + start);
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            }

            document.querySelectorAll('.number').forEach((element) => {
                const endValue = parseFloat(element.getAttribute('data-value').replace(/,/g, ''));
                animateValue(element, 0, endValue, 500);
            });
        });
    </script>
</html>
