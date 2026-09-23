{{-- Zelfstandige foutpagina (500/503): geen site-layout, geen Vite-assets en
     geen database, zodat 'ie ook werkt als juist dáár de fout zit. --}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #15130f; color: #f2eee4;
               font: 16px/1.6 system-ui, -apple-system, "Segoe UI", sans-serif; padding: 24px; box-sizing: border-box; }
        main { max-width: 560px; }
        .code { font: 600 12px/1 ui-monospace, monospace; letter-spacing: .2em; text-transform: uppercase; color: #f26178; }
        h1 { font-size: clamp(28px, 6vw, 44px); line-height: 1.05; text-transform: uppercase; margin: 16px 0; }
        p { color: rgba(242, 238, 228, .78); }
        a { display: inline-block; margin-top: 16px; margin-right: 8px; padding: 10px 18px; border-radius: 3px;
            background: #d90429; color: #f2eee4; text-decoration: none; font-weight: 500; }
        a.alt { background: transparent; border: 1px solid rgba(242, 238, 228, .25); }
    </style>
</head>
<body>
    <main>
        <p class="code">Foutcode {{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="/">Naar de homepage</a>
        <a class="alt" href="tel:{{ config('brand.contact.phone_href') }}">Bel {{ config('brand.contact.phone') }}</a>
    </main>
</body>
</html>
