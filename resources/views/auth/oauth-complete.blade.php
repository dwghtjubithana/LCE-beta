<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OAuth login</title>
    <style>
        body { margin: 0; font-family: Inter, sans-serif; background: #f8fafc; color: #334155; }
        .wrap { max-width: 560px; margin: 80px auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; }
        .ok { color: #166534; }
        .err { color: #b91c1c; }
        a { color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>OAuth login</h1>
        <p class="{{ $ok ? 'ok' : 'err' }}">{{ $message ?? 'Resultaat onbekend.' }}</p>
        <p><a href="/">Terug</a></p>
    </div>
    @if($ok && !empty($token))
    <script>
        localStorage.setItem('lce_token', @json($token));
        window.location.href = '/dashboard';
    </script>
    @endif
</body>
</html>

