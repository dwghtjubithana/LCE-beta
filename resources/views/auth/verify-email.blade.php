<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email verificatie</title>
    <style>
        body { margin: 0; font-family: Inter, sans-serif; background: #f8fafc; color: #334155; }
        .wrap { max-width: 560px; margin: 80px auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p { margin: 0 0 10px; color: #64748b; }
        .ok { color: #166534; }
        .err { color: #b91c1c; }
        a { color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Email verificatie</h1>
        <p id="status">Verificatie wordt uitgevoerd...</p>
        <p><a href="/">Terug naar inloggen</a></p>
    </div>
    <script>
        (async () => {
            const statusEl = document.getElementById('status');
            const qs = new URLSearchParams(window.location.search);
            const uid = qs.get('uid');
            const token = qs.get('token');

            if (!uid || !token) {
                statusEl.textContent = 'Ongeldige verificatielink.';
                statusEl.className = 'err';
                return;
            }

            try {
                const res = await fetch('/api/auth/verify-email', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ uid, token }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    statusEl.textContent = data.message || 'Verificatie mislukt.';
                    statusEl.className = 'err';
                    return;
                }
                statusEl.textContent = data.message || 'Je e-mailadres is geverifieerd.';
                statusEl.className = 'ok';
            } catch (err) {
                statusEl.textContent = 'Netwerkfout tijdens verificatie.';
                statusEl.className = 'err';
            }
        })();
    </script>
</body>
</html>

