<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="{{ config('app.name') }} API Documentation" />
    <title>{{ config('app.name') }} - API Documentation</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <style nonce="{{ csp_nonce() }}">
        html,
        body {
            margin: 0;
            height: 100%;
        }

        body.coconut-api-docs {
            display: flex;
            flex-direction: column;
        }

        .coconut-api-docs-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-shrink: 0;
            padding: 0.75rem 1.25rem;
            background: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .coconut-api-docs-header .coconut-logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .coconut-api-docs-header .coconut-logo-link img {
            display: block;
            width: 180px;
            max-width: min(180px, 70vw);
            height: auto;
        }

        .coconut-api-docs-header .coconut-api-docs-title {
            margin-left: auto;
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            color: #000000;
        }

        #app {
            flex: 1;
            min-height: 0;
        }

        .light-mode {
            --scalar-color-1: #000000;
            --scalar-color-2: rgba(0, 0, 0, 0.7);
            --scalar-color-3: rgba(0, 0, 0, 0.5);
            --scalar-color-accent: #32994d;
            --scalar-background-1: #ffffff;
            --scalar-background-2: #f6f5f4;
            --scalar-background-3: #f1ede9;
            --scalar-background-accent: #32994d1f;
            --scalar-border-color: rgba(0, 0, 0, 0.08);
        }

        .light-mode .sidebar {
            --scalar-sidebar-background-1: var(--scalar-background-1);
            --scalar-sidebar-item-hover-color: currentColor;
            --scalar-sidebar-item-hover-background: var(--scalar-background-2);
            --scalar-sidebar-item-active-background: var(--scalar-background-2);
            --scalar-sidebar-border-color: var(--scalar-border-color);
            --scalar-sidebar-color-1: #000000;
            --scalar-sidebar-color-2: rgba(0, 0, 0, 0.7);
            --scalar-sidebar-color-active: var(--scalar-color-accent);
            --scalar-sidebar-search-background: var(--scalar-background-2);
            --scalar-sidebar-search-border-color: var(--scalar-border-color);
            --scalar-sidebar-search-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="coconut-api-docs">
    <header class="coconut-api-docs-header">
        <a href="{{ url('/') }}" class="coconut-logo-link" title="{{ config('app.name') }}">
            <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name') }}" />
        </a>
        <span class="coconut-api-docs-title">API Documentation</span>
    </header>
    <div id="app"></div>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.36.0" nonce="{{ csp_nonce() }}" crossorigin></script>
    <script nonce="{{ csp_nonce() }}">
        Scalar.createApiReference('#app', {
            url: '/vendor/rest/openapi.json',
            layout: 'modern',
            theme: 'none',
            darkMode: false,
            showDeveloperTools: 'never',
            metaData: {
                title: @json(config('app.name') . ' API Documentation'),
                ogTitle: @json(config('app.name') . ' API Documentation'),
                ogImage: @json(asset('img/coconut-og-image.png')),
            },
        });
    </script>
</body>
</html>
