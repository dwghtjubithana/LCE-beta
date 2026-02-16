<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'LCE Admin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #334155;
            --muted: #64748b;
            --line: #e2e8f0;
            --card: #ffffff;
            --bg: #f4f7f9;
            --accent: #0ea5a4;
            --accent-2: #0c8e8d;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", system-ui, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }
        a { color: inherit; text-decoration: none; }
        .app {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            min-height: 100vh;
        }
        .sidebar {
            background: #1e293b;
            color: #e2e8f0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .brand {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .brand h1 {
            font-size: 18px;
            margin: 0;
            letter-spacing: 0.03em;
        }
        .brand h1 .accent {
            color: var(--accent);
            font-weight: 800;
        }
        .brand span {
            font-size: 12px;
            color: #94a3b8;
        }
        .nav {
            display: grid;
            gap: 6px;
        }
        .nav a {
            padding: 10px 12px;
            border-radius: 10px;
            color: #cbd5e1;
            font-weight: 600;
        }
        .nav a.active {
            background: rgba(14, 165, 164, 0.15);
            color: var(--accent);
            border-right: 3px solid var(--accent);
        }
        .nav h3 {
            margin: 16px 0 6px;
            font-size: 11px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .sidebar-footer {
            margin-top: auto;
            display: grid;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-weight: 600;
            cursor: pointer;
            background: var(--accent);
            color: white;
        }
        .btn.secondary {
            background: white;
            border-color: var(--line);
            color: var(--ink);
        }
        .btn.danger {
            background: var(--danger);
            border-color: var(--danger);
            color: white;
        }
        .main {
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: white;
            border-bottom: 1px solid var(--line);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .search {
            flex: 1;
            max-width: 420px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 10px;
            color: var(--muted);
        }
        .search input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-family: inherit;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pill {
            padding: 6px 10px;
            border-radius: 999px;
            background: #e6fffa;
            font-size: 12px;
            color: #0f766e;
            font-weight: 600;
        }
        .content {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .page-header h2 {
            margin: 0;
            font-size: 22px;
        }
        .page-header p {
            margin: 4px 0 0;
            color: var(--muted);
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .table th, .table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            text-align: left;
        }
        .table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .form-stack {
            display: grid;
            gap: 14px;
        }
        .form-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .input, select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--line);
            font-family: inherit;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: #e6fffa;
            color: #0f766e;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warn { background: #fef9c3; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .status {
            font-size: 13px;
            color: var(--muted);
        }
        .status.error { color: var(--danger); }
        .status.success { color: var(--success); }
        .status.warning { color: var(--warning); }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .muted { color: var(--muted); }
        @media (max-width: 960px) {
            .app { grid-template-columns: 1fr; }
            .sidebar { position: static; }
        }
    </style>
</head>
<body>
    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <h1>Wap<span class="accent">core</span> Admin</h1>
                <span>Local Content Engine</span>
            </div>
            <nav class="nav">
                <a href="/admin" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">Dashboard</a>
                <h3>Core</h3>
                <a href="/admin/users" class="{{ ($active ?? '') === 'users' ? 'active' : '' }}">Users</a>
                <a href="/admin/companies" class="{{ ($active ?? '') === 'companies' ? 'active' : '' }}">Companies</a>
                <a href="/admin/documents" class="{{ ($active ?? '') === 'documents' ? 'active' : '' }}">Documenten</a>
                <a href="/admin/tenders" class="{{ ($active ?? '') === 'tenders' ? 'active' : '' }}">Tenders</a>
                <h3>Management</h3>
                <a href="/admin/compliance-rules" class="{{ ($active ?? '') === 'rules' ? 'active' : '' }}">Compliance Rules</a>
                <a href="/admin/notifications" class="{{ ($active ?? '') === 'notifications' ? 'active' : '' }}">Notifications</a>
                <a href="/admin/payment-proofs" class="{{ ($active ?? '') === 'payment-proofs' ? 'active' : '' }}">Payment Proofs</a>
                <a href="/admin/audit-logs" class="{{ ($active ?? '') === 'logs' ? 'active' : '' }}">Audit Logs</a>
                <a href="/admin/system" class="{{ ($active ?? '') === 'system' ? 'active' : '' }}">Systeemstatus</a>
                <a href="/admin/ai-settings" class="{{ ($active ?? '') === 'ai-settings' ? 'active' : '' }}">AI-instellingen</a>
            </nav>
            <div class="sidebar-footer">
                <button class="btn secondary" id="btn-logout">Sign out</button>
            </div>
        </aside>

        <div class="main">
            <div class="topbar">
                <div class="search">
                    <span>🔍</span>
                    <input type="text" placeholder="Zoek gebruikers, bedrijven, aanbestedingen">
                </div>
                <div class="topbar-right">
                    <span class="pill">Notifications</span>
                    <span class="pill" id="admin-user">Beheerder</span>
                </div>
            </div>
            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        const AdminApp = {
            tokenKey: 'lce_admin_token',
            getToken() {
                return localStorage.getItem(this.tokenKey) || '';
            },
            setToken(token) {
                if (token) localStorage.setItem(this.tokenKey, token);
                else localStorage.removeItem(this.tokenKey);
            },
            requireAuth() {
                if (!this.getToken()) {
                    const next = encodeURIComponent(window.location.pathname);
                    window.location.href = `/admin/login?next=${next}`;
                }
            },
            async api(path, options = {}) {
                const headers = options.headers || {};
                const token = this.getToken();
                if (token) headers['Authorization'] = `Bearer ${token}`;
                const res = await fetch(path, { ...options, headers });
                if (res.status === 401) {
                    this.setToken('');
                    this.requireAuth();
                }
                return res;
            },
            async readJson(res) {
                const text = await res.text();
                if (!text) return null;
                try {
                    return JSON.parse(text);
                } catch (error) {
                    return text;
                }
            },
            formatError(payload) {
                if (!payload) return 'Request failed. Please try again.';
                if (typeof payload === 'string') return payload;
                const message = payload.message || payload.error || 'Request failed.';
                const fieldErrors = payload.fieldErrors || payload.errors;
                if (fieldErrors && typeof fieldErrors === 'object') {
                    const details = Object.entries(fieldErrors)
                        .map(([field, value]) => {
                            if (Array.isArray(value)) return `${field}: ${value.join(', ')}`;
                            return `${field}: ${String(value)}`;
                        })
                        .join(' • ');
                    return details ? `${message} (${details})` : message;
                }
                return message;
            },
            setStatus(target, message, type = 'error') {
                if (!target) return;
                target.textContent = message;
                target.className = `status ${type}`;
            },
            formatDateTime(value) {
                if (!value) return '—';
                const d = new Date(value);
                if (Number.isNaN(d.getTime())) return String(value);
                return d.toLocaleString('nl-NL', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            },
            formatDate(value) {
                if (!value) return '—';
                const d = new Date(value);
                if (Number.isNaN(d.getTime())) return String(value);
                return d.toLocaleDateString('nl-NL', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                });
            },
            async initTopbar() {
                const token = this.getToken();
                if (!token) return;
                const res = await this.api('/api/auth/me');
                if (!res.ok) return;
                const data = await this.readJson(res);
                const label = data?.user?.email || data?.user?.username || 'Admin';
                const target = document.getElementById('admin-user');
                if (target) target.textContent = label;
            }
        };

        document.getElementById('btn-logout')?.addEventListener('click', () => {
            AdminApp.setToken('');
            window.location.href = '/admin/login';
        });
    </script>
    @yield('scripts')
</body>
</html>
