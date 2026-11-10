<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · Roosko</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: #fafafa; color: #18181b;
               font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 30rem; width: 100%; }
        img { height: 40px; display: block; margin-bottom: 32px; }
        .code { font-size: .9rem; font-weight: 600; color: #0f766e; letter-spacing: .05em; }
        h1 { font-size: 1.5rem; margin: 6px 0 10px; }
        p { color: #52525b; line-height: 1.5; margin: 0 0 24px; }
        a { color: #0f766e; font-weight: 600; text-decoration: none; }
        a:hover, a:focus-visible { text-decoration: underline; }
    </style>
</head>
<body>
<main>
    <img src="{{ asset('images/roosko-logo-light.svg') }}" alt="Roosko">
    <div class="code">@yield('code')</div>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a href="{{ url('/') }}">{{ __('roosko::errors.home') }}</a>
</main>
</body>
</html>
