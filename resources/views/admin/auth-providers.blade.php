@extends('admin.layout')

@section('title', 'Auth providers')
@php($active = 'auth-providers')

@section('content')
@include('admin.partials.settings-subnav')

<div class="page-header">
    <div>
        <h2>Auth providers</h2>
        <p>Beheer Google en Microsoft login. Secrets worden versleuteld opgeslagen.</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Google</h3>
    <div class="form-stack">
        <div class="form-field">
            <label for="google-enabled">Enabled</label>
            <select id="google-enabled">
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
        </div>
        <div class="form-field">
            <label for="google-client-id">Client ID</label>
            <input class="input" id="google-client-id" placeholder="Google OAuth client id">
        </div>
        <div class="form-field">
            <label for="google-client-secret">Client Secret</label>
            <input class="input" id="google-client-secret" type="password" placeholder="Leeg laten om huidige te behouden">
            <p class="status" id="google-secret-status"></p>
        </div>
        <div class="form-field">
            <label for="google-redirect-uri">Redirect URI</label>
            <input class="input" id="google-redirect-uri" placeholder="https://domein/auth/oauth/google/callback">
        </div>
        <div class="form-field">
            <label for="google-prompt">Prompt</label>
            <select id="google-prompt">
                <option value="select_account">select_account</option>
                <option value="consent">consent</option>
                <option value="none">none</option>
            </select>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Microsoft</h3>
    <div class="form-stack">
        <div class="form-field">
            <label for="microsoft-enabled">Enabled</label>
            <select id="microsoft-enabled">
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
        </div>
        <div class="form-field">
            <label for="microsoft-client-id">Client ID</label>
            <input class="input" id="microsoft-client-id" placeholder="Microsoft app client id">
        </div>
        <div class="form-field">
            <label for="microsoft-client-secret">Client Secret</label>
            <input class="input" id="microsoft-client-secret" type="password" placeholder="Leeg laten om huidige te behouden">
            <p class="status" id="microsoft-secret-status"></p>
        </div>
        <div class="form-field">
            <label for="microsoft-tenant">Tenant</label>
            <input class="input" id="microsoft-tenant" placeholder="common">
        </div>
        <div class="form-field">
            <label for="microsoft-redirect-uri">Redirect URI</label>
            <input class="input" id="microsoft-redirect-uri" placeholder="https://domein/auth/oauth/microsoft/callback">
        </div>
    </div>
</div>

<div class="card">
    <div class="actions">
        <button class="btn" id="btn-save-auth-providers">Auth provider instellingen opslaan</button>
        <button class="btn secondary" id="btn-test-auth-providers">Diagnostiek testen</button>
    </div>
    <div class="status" id="auth-provider-status"></div>
    <div class="status" id="auth-provider-diagnostics" style="margin-top:10px;"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    function escapeHtml(value) {
        const str = String(value ?? '');
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    async function loadSettings() {
        const statusEl = document.getElementById('auth-provider-status');
        const res = await AdminApp.api('/api/admin/auth-providers');
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data) || 'Laden mislukt.', 'error');
            return;
        }
        const s = data.settings || {};
        document.getElementById('google-enabled').value = s.auth_google_enabled ? '1' : '0';
        document.getElementById('google-client-id').value = s.auth_google_client_id || '';
        document.getElementById('google-client-secret').value = '';
        document.getElementById('google-redirect-uri').value = s.auth_google_redirect_uri || '';
        const promptEl = document.getElementById('google-prompt');
        const promptValue = s.auth_google_prompt || 'select_account';
        if (!Array.from(promptEl.options).some((opt) => opt.value === promptValue)) {
            const opt = document.createElement('option');
            opt.value = promptValue;
            opt.textContent = promptValue;
            promptEl.appendChild(opt);
        }
        promptEl.value = promptValue;
        document.getElementById('google-secret-status').textContent = s.auth_google_client_secret_set ? 'Secret is ingesteld.' : 'Nog geen secret opgeslagen.';

        document.getElementById('microsoft-enabled').value = s.auth_microsoft_enabled ? '1' : '0';
        document.getElementById('microsoft-client-id').value = s.auth_microsoft_client_id || '';
        document.getElementById('microsoft-client-secret').value = '';
        document.getElementById('microsoft-tenant').value = s.auth_microsoft_tenant || 'common';
        document.getElementById('microsoft-redirect-uri').value = s.auth_microsoft_redirect_uri || '';
        document.getElementById('microsoft-secret-status').textContent = s.auth_microsoft_client_secret_set ? 'Secret is ingesteld.' : 'Nog geen secret opgeslagen.';
    }

    async function saveSettings() {
        const statusEl = document.getElementById('auth-provider-status');
        const payload = {
            auth_google_enabled: document.getElementById('google-enabled').value === '1',
            auth_google_client_id: document.getElementById('google-client-id').value.trim() || null,
            auth_google_client_secret: document.getElementById('google-client-secret').value.trim() || null,
            auth_google_redirect_uri: document.getElementById('google-redirect-uri').value.trim() || null,
            auth_google_prompt: document.getElementById('google-prompt').value.trim() || null,
            auth_microsoft_enabled: document.getElementById('microsoft-enabled').value === '1',
            auth_microsoft_client_id: document.getElementById('microsoft-client-id').value.trim() || null,
            auth_microsoft_client_secret: document.getElementById('microsoft-client-secret').value.trim() || null,
            auth_microsoft_tenant: document.getElementById('microsoft-tenant').value.trim() || null,
            auth_microsoft_redirect_uri: document.getElementById('microsoft-redirect-uri').value.trim() || null,
        };

        const res = await AdminApp.api('/api/admin/auth-providers', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data) || 'Opslaan mislukt.', 'error');
            return;
        }
        AdminApp.setStatus(statusEl, data.message || 'Opgeslagen.', 'success');
        await loadSettings();
    }

    function renderDiagnostics(diag) {
        const target = document.getElementById('auth-provider-diagnostics');
        if (!target) return;
        const google = diag?.google || {};
        const microsoft = diag?.microsoft || {};
        const googleIssues = Array.isArray(google.issues) ? google.issues : [];
        const microsoftIssues = Array.isArray(microsoft.issues) ? microsoft.issues : [];
        const msMeta = microsoft.meta || {};

        target.innerHTML = `
            <div class="card" style="padding:12px; margin-top:8px;">
                <div class="status"><strong>Google</strong> — ${googleIssues.length ? 'Attention needed' : 'Ready'}</div>
                <div class="status">${googleIssues.length ? googleIssues.map((x) => escapeHtml(String(x))).join(' • ') : 'Geen issues gevonden.'}</div>
                <div class="status" style="margin-top:8px;"><strong>Microsoft</strong> — ${microsoftIssues.length ? 'Attention needed' : 'Ready'}</div>
                <div class="status">${microsoftIssues.length ? microsoftIssues.map((x) => escapeHtml(String(x))).join(' • ') : 'Geen issues gevonden.'}</div>
                <div class="status" style="margin-top:6px;">OIDC discovery: ${msMeta.oidc_discovery_ok ? 'OK' : 'FAILED'} | JWKS: ${msMeta.jwks_ok ? 'OK' : 'FAILED'} | Keys: ${msMeta.jwks_keys_count ?? 0}</div>
            </div>
        `;
    }

    async function testDiagnostics() {
        const statusEl = document.getElementById('auth-provider-status');
        const res = await AdminApp.api('/api/admin/auth-providers/diagnostics');
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data) || 'Diagnostiek mislukt.', 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'Diagnostiek uitgevoerd.', 'success');
        renderDiagnostics(data.diagnostics || {});
    }

    document.getElementById('btn-save-auth-providers').addEventListener('click', saveSettings);
    document.getElementById('btn-test-auth-providers').addEventListener('click', testDiagnostics);
    loadSettings();
    testDiagnostics();
</script>
@endsection
