@php
    $logo = asset('images/roosko-logo-light.svg');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('roosko::public_home.title') }}</title>
    <style>
        :root { color-scheme: light; --brand: #0f766e; --ink: #18181b; --muted: #52525b; --line: #e4e4e7; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px;
               font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; color: var(--ink); background: #fafafa; }
        main { width: 100%; max-width: 30rem; }
        img { height: 48px; display: block; margin-bottom: 28px; }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        p { margin: 0 0 28px; color: var(--muted); line-height: 1.5; }
        a.card { display: block; text-decoration: none; color: inherit; background: #fff; border: 1px solid var(--line);
                 border-radius: 12px; padding: 18px 20px; margin-bottom: 12px; }
        a.card:hover, a.card:focus-visible { border-color: var(--brand); outline: none; box-shadow: 0 0 0 3px rgb(15 118 110 / 15%); }
        a.card strong { display: block; font-size: 1.05rem; color: var(--brand); margin-bottom: 4px; }
        a.card span { color: var(--muted); font-size: .95rem; }
        footer { margin-top: 28px; font-size: .85rem; color: var(--muted); }
    </style>
</head>
<body>
<main>
    <img src="{{ $logo }}" alt="Roosko">
    <h1>{{ __('roosko::public_home.heading') }}</h1>
    <p>{{ __('roosko::public_home.intro') }}</p>
    <a class="card" href="{{ $plantUrl }}">
        <strong>{{ __('roosko::public_home.plant.title') }}</strong>
        <span>{{ __('roosko::public_home.plant.description') }}</span>
    </a>
    <a class="card" href="{{ $customerUrl }}">
        <strong>{{ __('roosko::public_home.customer.title') }}</strong>
        <span>{{ __('roosko::public_home.customer.description') }}</span>
    </a>
    <footer>{{ __('roosko::public_home.footer') }}</footer>
</main>
</body>
</html>
