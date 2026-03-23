@extends('admin.layout')

@section('title', 'Settings')
@php($active = 'settings')

@section('content')
@include('admin.partials.settings-subnav')

<div class="page-header">
    <div>
        <h2>Global Settings</h2>
        <p>Kies een sectie om instellingen te beheren. Alles staat nu gegroepeerd onder Settings.</p>
    </div>
</div>

<div class="grid">
    <a href="/admin/system" class="card" style="display:block;">
        <h3 style="margin-top:0;">System Status</h3>
        <p class="status">Gezondheid, metrics, en runtime status.</p>
    </a>
    <a href="/admin/ai-settings" class="card" style="display:block;">
        <h3 style="margin-top:0;">Document Analysis</h3>
        <p class="status">Gemini modellen, guardrails, en OCR-beleid.</p>
    </a>
    <a href="/admin/email-settings" class="card" style="display:block;">
        <h3 style="margin-top:0;">Email Settings</h3>
        <p class="status">SMTP, templates, verification links en logs.</p>
    </a>
    <a href="/admin/auth-providers" class="card" style="display:block;">
        <h3 style="margin-top:0;">Auth Providers</h3>
        <p class="status">Google en Microsoft OAuth configuratie.</p>
    </a>
</div>

<div class="card">
    <h3 style="margin-top:0;">Global Runtime Controls</h3>
    <p class="status">Centrale instellingen voor documentanalyse, veiligheid en runtime gedrag.</p>
    <div class="form-stack">
        <div class="form-field">
            <label for="settings-ai-retries">Analyse-validatie retries</label>
            <input class="input" id="settings-ai-retries" type="number" min="0" max="3" placeholder="1">
            <p class="status">Aantal extra pogingen wanneer Gemini validatie een error geeft.</p>
        </div>
        <div class="form-field">
            <label for="settings-gemini-timeout">Gemini timeout (seconden)</label>
            <input class="input" id="settings-gemini-timeout" type="number" min="5" max="180" placeholder="60">
            <p class="status">Maximale wachttijd voor Gemini requests.</p>
        </div>
        <div class="form-field">
            <label for="settings-debug-paths">Interne debugpaden opslaan</label>
            <select id="settings-debug-paths">
                <option value="0">Disabled (Recommended)</option>
                <option value="1">Enabled</option>
            </select>
            <p class="status">Alleen inschakelen voor troubleshooting; kan interne serverpaden bevatten.</p>
        </div>
        <div class="form-field">
            <label for="settings-expose-debug-user">Debug metadata tonen aan users</label>
            <select id="settings-expose-debug-user">
                <option value="0">Disabled (Recommended)</option>
                <option value="1">Enabled</option>
            </select>
            <p class="status">Voor productie meestal uitgeschakeld houden.</p>
        </div>
        <hr style="border:0; border-top:1px solid #e5e7eb; margin:6px 0 10px;">
        <div class="form-field">
            <label for="settings-scan-mode">Upload malware scan mode</label>
            <select id="settings-scan-mode">
                <option value="ENFORCE">ENFORCE (blokkeer geïnfecteerde uploads)</option>
                <option value="WARN">WARN (log waarschuwing, upload gaat door)</option>
                <option value="OFF">OFF (scan uitgeschakeld)</option>
            </select>
            <p class="status">ENFORCE is aanbevolen in productie met een werkende scanner.</p>
        </div>
        <div class="form-field">
            <label for="settings-scan-timeout">Upload scan timeout (seconden)</label>
            <input class="input" id="settings-scan-timeout" type="number" min="5" max="120" placeholder="20">
            <p class="status">Maximale tijd voor scan per uploadbestand.</p>
        </div>
        <div class="form-field">
            <label for="settings-scan-binary">Scanner command</label>
            <input class="input" id="settings-scan-binary" type="text" placeholder="clamscan">
            <p class="status">Voorbeeld: <code>clamscan</code> of volledig pad naar de scanner executable.</p>
        </div>
        <div class="form-field">
            <label for="settings-scan-block-error">Upload blokkeren bij scanfout</label>
            <select id="settings-scan-block-error">
                <option value="1">Enabled (strict)</option>
                <option value="0">Disabled (recommended while onboarding)</option>
            </select>
            <p class="status">Als scanner niet beschikbaar is, kun je uploads blokkeren of toestaan.</p>
        </div>
        <div class="form-field">
            <label>Laatste scannerfout</label>
            <div class="status" id="settings-scan-last-error">-</div>
        </div>
        <div class="actions">
            <button class="btn" id="btn-scan-health-check" type="button">Test scanner nu</button>
            <span class="status" id="scan-health-status"></span>
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-save-global-settings">Global settings opslaan</button>
    </div>
    <div class="status" id="global-settings-status"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    async function loadGlobalSettings() {
        const statusEl = document.getElementById('global-settings-status');
        const res = await AdminApp.api('/api/admin/settings');
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data) || 'Global settings laden mislukt.', 'error');
            return;
        }
        const s = data.settings || {};
        document.getElementById('settings-ai-retries').value = s.ai_validation_retry_count ?? 1;
        document.getElementById('settings-gemini-timeout').value = s.gemini_timeout_seconds ?? 60;
        document.getElementById('settings-debug-paths').value = s.ai_include_internal_debug_paths ? '1' : '0';
        document.getElementById('settings-expose-debug-user').value = s.ai_expose_debug_meta_to_user ? '1' : '0';
        document.getElementById('settings-scan-mode').value = s.upload_malware_scan_mode ?? 'OFF';
        document.getElementById('settings-scan-timeout').value = s.upload_malware_scan_timeout_seconds ?? 20;
        document.getElementById('settings-scan-binary').value = s.upload_malware_scan_binary ?? 'clamscan';
        document.getElementById('settings-scan-block-error').value = s.upload_malware_scan_block_on_error ? '1' : '0';
        const lastErrorAt = s.upload_malware_scan_last_error_at || '';
        const lastError = s.upload_malware_scan_last_error || 'Geen scannerfouten geregistreerd.';
        document.getElementById('settings-scan-last-error').textContent = lastErrorAt ? `${lastErrorAt} - ${lastError}` : lastError;
    }

    async function saveGlobalSettings() {
        const statusEl = document.getElementById('global-settings-status');
        const payload = {
            ai_validation_retry_count: Number(document.getElementById('settings-ai-retries').value),
            gemini_timeout_seconds: Number(document.getElementById('settings-gemini-timeout').value),
            ai_include_internal_debug_paths: document.getElementById('settings-debug-paths').value === '1',
            ai_expose_debug_meta_to_user: document.getElementById('settings-expose-debug-user').value === '1',
            upload_malware_scan_mode: document.getElementById('settings-scan-mode').value,
            upload_malware_scan_timeout_seconds: Number(document.getElementById('settings-scan-timeout').value),
            upload_malware_scan_binary: document.getElementById('settings-scan-binary').value.trim(),
            upload_malware_scan_block_on_error: document.getElementById('settings-scan-block-error').value === '1',
        };

        const res = await AdminApp.api('/api/admin/settings', {
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
        await loadGlobalSettings();
    }

    async function runScannerHealthCheck() {
        const statusEl = document.getElementById('scan-health-status');
        AdminApp.setStatus(statusEl, 'Scanner health check loopt...', 'info');
        const res = await AdminApp.api('/api/admin/settings/scanner-health');
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data) || 'Scanner health check mislukt.', 'error');
            return;
        }

        const h = data.health || {};
        if (h.available) {
            AdminApp.setStatus(statusEl, `Scanner beschikbaar (${h.version || h.binary}).`, 'success');
        } else {
            AdminApp.setStatus(statusEl, h.error || 'Scanner niet beschikbaar.', 'error');
        }
        await loadGlobalSettings();
    }

    document.getElementById('btn-save-global-settings').addEventListener('click', saveGlobalSettings);
    document.getElementById('btn-scan-health-check').addEventListener('click', runScannerHealthCheck);
    loadGlobalSettings();
</script>
@endsection
