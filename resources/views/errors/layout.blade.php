<!DOCTYPE html>
<html lang="sk" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · {{ config('app.name') }}</title>
    {{-- Self-contained styles: error pages must work even when the asset build is missing. --}}
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f8fafc; color: #0f172a; }
        main { max-width: 28rem; padding: 2rem; text-align: center; }
        .code { font-size: 4rem; font-weight: 800; color: #1c3763; margin: 0; }
        h1 { font-size: 1.25rem; margin: .5rem 0; }
        p { color: #475569; line-height: 1.6; }
        a { display: inline-block; margin-top: 1rem; padding: .6rem 1.2rem; border-radius: .5rem; background: #1c3763; color: #fff; text-decoration: none; font-weight: 600; }
        a:focus-visible { outline: 3px solid #8ea6cb; outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <p class="code">{{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">{{ __('Späť na úvod') }}</a>
    </main>
</body>
</html>
