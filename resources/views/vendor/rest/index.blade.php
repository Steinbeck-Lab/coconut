<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="SwaggerUI" />
    <title>{{ config('app.name') }} - API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.4.2/swagger-ui.css" nonce="{{ csp_nonce() }}" />
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.4.2/swagger-ui-bundle.js" nonce="{{ csp_nonce() }}" crossorigin></script>
    <script nonce="{{ csp_nonce() }}">
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: '/vendor/rest/openapi.json',
                dom_id: '#swagger-ui',
            });
        };
    </script>
</body>
</html>
