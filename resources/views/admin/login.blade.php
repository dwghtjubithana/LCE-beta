<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wapcore Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#334155; --muted:#64748b; --line:#e2e8f0; --bg:#f4f7f9; --accent:#0ea5a4; --accent-strong:#0c8e8d; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Inter",sans-serif; background:var(--bg); color:var(--ink); }
        .wrap { min-height:100vh; display:grid; place-items:center; padding:24px; }
        .card { background:white; border:1px solid var(--line); border-radius:14px; padding:24px; width:100%; max-width:420px; }
        h1 { margin:0 0 8px; font-size:22px; letter-spacing:-0.02em; }
        p { margin:0 0 20px; color:var(--muted); }
        label { font-size:12px; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); }
        input { width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--line); margin:6px 0 14px; }
        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(14,165,164,.15); }
        button { width:100%; padding:10px 14px; border-radius:10px; border:none; background:var(--accent); color:white; font-weight:600; cursor:pointer; }
        button:hover { background: var(--accent-strong); }
        .status { margin-top:12px; font-size:13px; color:var(--muted); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Wapcore Admin Login</h1>
            <p>Sign in to manage users, companies, documents, and tenders.</p>
            <label>Email</label>
            <input id="email" type="email" placeholder="admin@example.com">
            <label>Password</label>
            <input id="password" type="password" placeholder="••••••••">
            <button id="btn-login">Sign in</button>
            <div class="status" id="status"></div>
        </div>
    </div>

    <script>
        const tokenKey = 'lce_admin_token';
        const statusEl = document.getElementById('status');
        document.getElementById('btn-login').addEventListener('click', async () => {
            statusEl.textContent = '';
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            if (!email || !password) {
                statusEl.textContent = 'Email and password are required.';
                return;
            }
            const res = await fetch('/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            const data = await res.json();
            if (!res.ok) {
                statusEl.textContent = data.message || 'Login failed.';
                return;
            }
            localStorage.setItem(tokenKey, data.token);
            const next = new URLSearchParams(window.location.search).get('next') || '/admin';
            window.location.href = next;
        });
    </script>
</body>
</html>
