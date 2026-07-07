<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WrkPlan API Portal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        :root {
            --bg: #0b1220;
            --surface: #111a2d;
            --card: #16213a;
            --text: #d9e4ff;
            --muted: #90a2c6;
            --accent: #34d399;
            --accent-2: #60a5fa;
            --border: rgba(148, 163, 184, 0.25);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at 10% 20%, #1b2a4a 0%, #0b1220 55%), linear-gradient(120deg, #0f172a, #0b1220);
            min-height: 100vh;
        }
        .shell {
            max-width: 1320px;
            margin: 0 auto;
            padding: 24px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 18px;
        }
        .panel {
            background: rgba(22, 33, 58, 0.85);
            border: 1px solid var(--border);
            border-radius: 18px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(2, 6, 23, 0.45);
        }
        .left {
            padding: 18px;
            position: sticky;
            top: 14px;
            height: fit-content;
        }
        .title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .title h1 {
            margin: 0;
            font-size: 1.15rem;
            letter-spacing: .2px;
        }
        .badge {
            font-size: .75rem;
            color: #042f2e;
            background: linear-gradient(135deg, #6ee7b7, #34d399);
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 700;
        }
        .field {
            margin-bottom: 12px;
        }
        .field label {
            display: block;
            font-size: .8rem;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .field input {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #0d1629;
            color: var(--text);
            padding: 10px 12px;
        }
        .hint {
            color: var(--muted);
            font-size: .77rem;
            line-height: 1.4;
        }
        .quick-links a {
            display: block;
            margin-top: 8px;
            color: var(--accent-2);
            text-decoration: none;
            font-size: .86rem;
        }
        .swagger-wrap {
            padding: 8px;
        }
        #swagger-ui {
            background: #f8fafc;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .3);
            animation: fadeIn .4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 1024px) {
            .shell { grid-template-columns: 1fr; }
            .left { position: static; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="panel left">
            <div class="title">
                <h1>WrkPlan API Explorer</h1>
                <span class="badge">Interactive</span>
            </div>

            <div class="field">
                <label for="cookieName">Session Cookie Name</label>
                <input id="cookieName" type="text" value="wrkplan_session">
            </div>
            <div class="field">
                <label for="cookieValue">Session Cookie Value (optional override)</label>
                <input id="cookieValue" type="text" placeholder="Paste token/cookie value to test here">
            </div>
            <p class="hint">Swagger UI supports Try-It-Out with request/response payloads, headers, file uploads, and status codes. If your browser session is authenticated, requests will automatically include the current cookie.</p>

            <div class="quick-links">
                <a href="{{ url('/api/documentation') }}" target="_blank" rel="noopener">Open default Swagger route</a>
                <a href="{{ url('/docs/openapi.yaml') }}" target="_blank" rel="noopener">Open raw OpenAPI YAML</a>
                <a href="{{ url('/docs/database') }}">Open database visual docs</a>
            </div>
        </aside>

        <main class="panel swagger-wrap">
            <div id="swagger-ui"></div>
        </main>
    </div>

    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        const cookieNameInput = document.getElementById('cookieName');
        const cookieValueInput = document.getElementById('cookieValue');

        const ui = SwaggerUIBundle({
            url: '{{ url('/docs/openapi.yaml') }}',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset,
            ],
            layout: 'BaseLayout',
            defaultModelsExpandDepth: 1,
            displayRequestDuration: true,
            tryItOutEnabled: true,
            requestInterceptor: (req) => {
                const customCookieName = cookieNameInput.value.trim();
                const customCookieValue = cookieValueInput.value.trim();

                if (customCookieName && customCookieValue) {
                    const current = req.headers['Cookie'] || '';
                    const inject = `${customCookieName}=${customCookieValue}`;
                    req.headers['Cookie'] = current ? `${current}; ${inject}` : inject;
                }

                return req;
            },
        });

        window.swaggerUI = ui;
    </script>
</body>
</html>
