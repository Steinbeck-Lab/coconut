<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield("title", "Natural Products") - {{ config('app.name', 'COCONUT') }}</title>

    @section('meta')
<!-- Meta Tags -->
    <meta name="description"
    content="An aggregated dataset of elucidated and predicted natural products collected from open sources and a web interface to browse, search, and easily download NPs.">
    <meta name="keywords" content="natural products, COCONUT, open data, molecule database">
    <meta name="author" content="COCONUT">
    <meta property="og:title" content="COCONUT: COlleCtion of Open Natural prodUcTs">
    <meta property="og:description"
        content="An aggregated dataset of elucidated and predicted natural products collected from open sources and a web interface to browse, search, and easily download NPs">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/coconut-og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="config('app.name', 'COCONUT')">
    @show

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Matomo -->
    <script>
        var _paq = window._paq = window._paq || [];
        /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            var u = "//matomo.nfdi4chem.de/";
            _paq.push(['setTrackerUrl', u + 'matomo.php']);
            _paq.push(['setSiteId', '3']);
            var d = document,
                g = d.createElement('script'),
                s = d.getElementsByTagName('script')[0];
            g.async = true;
            g.src = u + 'matomo.js';
            s.parentNode.insertBefore(g, s);
        })();
    </script>
    <!-- End Matomo Code -->


    <!-- Styles -->
    @livewireStyles
</head>

<body>
    <x-banner />
    <x-impersonate::banner style='light' />
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
