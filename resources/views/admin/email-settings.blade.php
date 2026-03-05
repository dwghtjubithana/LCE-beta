@extends('admin.layout')

@section('title', 'Email-instellingen')
@php($active = 'email-settings')

@section('content')
<div class="page-header">
    <div>
        <h2>Email-instellingen</h2>
        <p>Beheer SMTP-gegevens, verzendregels en templates vanuit admin.</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">SMTP en verzendopties</h3>
    <div class="form-stack">
        <div class="form-field">
            <label for="email-enabled">Email verzenden ingeschakeld</label>
            <select id="email-enabled">
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
        </div>
        <div class="form-field">
            <label for="email-mailer">Mailer</label>
            <select id="email-mailer">
                <option value="smtp">smtp</option>
                <option value="sendmail">sendmail</option>
                <option value="log">log</option>
                <option value="array">array</option>
            </select>
        </div>
        <div class="form-field">
            <label for="email-smtp-host">SMTP host</label>
            <input class="input" id="email-smtp-host" placeholder="smtp.example.com">
        </div>
        <div class="form-field">
            <label for="email-smtp-port">SMTP port</label>
            <input class="input" id="email-smtp-port" type="number" min="1" max="65535" placeholder="587">
        </div>
        <div class="form-field">
            <label for="email-smtp-encryption">Encryptie</label>
            <select id="email-smtp-encryption">
                <option value="tls">tls</option>
                <option value="ssl">ssl</option>
                <option value="">none</option>
            </select>
        </div>
        <div class="form-field">
            <label for="email-smtp-username">SMTP username</label>
            <input class="input" id="email-smtp-username" placeholder="user@example.com">
        </div>
        <div class="form-field">
            <label for="email-smtp-password">SMTP password</label>
            <input class="input" id="email-smtp-password" type="password" placeholder="Laat leeg om huidige te behouden">
            <p class="status" id="email-smtp-password-status"></p>
        </div>
        <div class="form-field">
            <label for="email-from-name">From naam</label>
            <input class="input" id="email-from-name" placeholder="Wapcore LCE">
        </div>
        <div class="form-field">
            <label for="email-from-address">From email</label>
            <input class="input" id="email-from-address" type="email" placeholder="noreply@example.com">
        </div>
        <div class="form-field">
            <label for="email-reply-to-name">Reply-to naam</label>
            <input class="input" id="email-reply-to-name" placeholder="Support">
        </div>
        <div class="form-field">
            <label for="email-reply-to-address">Reply-to email</label>
            <input class="input" id="email-reply-to-address" type="email" placeholder="support@example.com">
        </div>
        <div class="form-field">
            <label for="email-verification-base-url">Verification base URL</label>
            <input class="input" id="email-verification-base-url" placeholder="https://app.example.com/verify-email">
        </div>
        <div class="form-field">
            <label for="email-verification-ttl">Verification token TTL (minuten)</label>
            <input class="input" id="email-verification-ttl" type="number" min="5" max="10080" placeholder="1440">
        </div>
        <div class="form-field">
            <label for="email-send-welcome">Welkom-mail</label>
            <select id="email-send-welcome">
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
        </div>
        <div class="form-field">
            <label for="email-send-verification">Verification-mail</label>
            <select id="email-send-verification">
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
        </div>
        <div class="form-field">
            <label for="email-send-notifications">Notificatie-mails</label>
            <select id="email-send-notifications">
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-email-save">Email-instellingen opslaan</button>
    </div>
    <div class="status" id="email-status"></div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Betaalgegevens (Upgrade)</h3>
    <div class="form-stack">
        <div class="form-field">
            <label for="payment-bank-name">Bank naam</label>
            <input class="input" id="payment-bank-name" placeholder="TCB">
        </div>
        <div class="form-field">
            <label for="payment-bank-account">Rekeningnummer</label>
            <input class="input" id="payment-bank-account" placeholder="12.34.56.789">
        </div>
        <div class="form-field">
            <label for="payment-bank-account-name">Rekeninghouder</label>
            <input class="input" id="payment-bank-account-name" placeholder="Wapcomtek NV">
        </div>
    </div>
    <div class="status">Deze waarden worden getoond op de upgradepagina voor gebruikers.</div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Templates</h3>
    <p class="status">Per emailtype kun je onderwerp en inhoud aanpassen.</p>
    <div id="template-list" class="form-stack"></div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn secondary" id="btn-template-save">Templates opslaan</button>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Test email</h3>
    <div class="form-stack">
        <div class="form-field">
            <label for="test-to-email">Naar email</label>
            <input class="input" id="test-to-email" type="email" placeholder="naam@bedrijf.sr">
        </div>
        <div class="form-field">
            <label for="test-template-key">Template key</label>
            <select id="test-template-key"></select>
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn secondary" id="btn-email-test">Stuur testmail</button>
    </div>
    <div class="status" id="email-test-status"></div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Recente email logs</h3>
    <div id="email-logs" class="status">Laden...</div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    let currentTemplates = [];

    function boolToSelect(value) {
        return value ? '1' : '0';
    }

    function valAsBool(id) {
        return document.getElementById(id).value === '1';
    }

    function ensureOption(selectId, value) {
        const el = document.getElementById(selectId);
        if (!el) return;
        const val = String(value ?? '');
        const exists = Array.from(el.options || []).some((opt) => String(opt.value) === val);
        if (!exists && val !== '') {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = val;
            el.appendChild(opt);
        }
    }

    function renderTemplates(templates) {
        const container = document.getElementById('template-list');
        if (!container) return;
        if (!templates.length) {
            container.innerHTML = '<div class="status">Geen templates gevonden.</div>';
            return;
        }

        container.innerHTML = templates.map((tpl, index) => `
            <div class="card" style="padding:12px;">
                <div class="form-stack">
                    <div class="form-field">
                        <label>Template key</label>
                        <input class="input tpl-key" data-index="${index}" value="${escapeHtml(tpl.template_key || '')}" readonly>
                    </div>
                    <div class="form-field">
                        <label>Naam</label>
                        <input class="input tpl-name" data-index="${index}" value="${escapeHtml(tpl.name || '')}">
                    </div>
                    <div class="form-field">
                        <label>Subject</label>
                        <input class="input tpl-subject" data-index="${index}" value="${escapeHtml(tpl.subject || '')}">
                    </div>
                    <div class="form-field">
                        <label>Body</label>
                        <textarea class="input tpl-body" data-index="${index}" rows="6">${escapeHtml(tpl.body || '')}</textarea>
                    </div>
                    <div class="form-field">
                        <label>Actief</label>
                        <select class="tpl-active" data-index="${index}">
                            <option value="1" ${tpl.is_active ? 'selected' : ''}>Enabled</option>
                            <option value="0" ${tpl.is_active ? '' : 'selected'}>Disabled</option>
                        </select>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function readTemplatesFromForm() {
        return currentTemplates.map((tpl, index) => ({
            template_key: tpl.template_key,
            name: document.querySelector(`.tpl-name[data-index="${index}"]`)?.value?.trim() || tpl.name,
            subject: document.querySelector(`.tpl-subject[data-index="${index}"]`)?.value?.trim() || tpl.subject,
            body: document.querySelector(`.tpl-body[data-index="${index}"]`)?.value || tpl.body,
            is_active: document.querySelector(`.tpl-active[data-index="${index}"]`)?.value === '1'
        }));
    }

    async function loadEmailSettings() {
        const statusEl = document.getElementById('email-status');
        const res = await AdminApp.api('/api/admin/email-settings');
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, data.message || 'Email settings laden mislukt.', 'error');
            return;
        }

        const s = data.settings || {};
        currentTemplates = Array.isArray(data.templates) ? data.templates : [];

        document.getElementById('email-enabled').value = boolToSelect(!!s.email_enabled);
        ensureOption('email-mailer', s.email_mailer || 'smtp');
        document.getElementById('email-mailer').value = s.email_mailer || 'smtp';
        document.getElementById('email-smtp-host').value = s.email_smtp_host || '';
        document.getElementById('email-smtp-port').value = s.email_smtp_port || '';
        ensureOption('email-smtp-encryption', s.email_smtp_encryption || '');
        document.getElementById('email-smtp-encryption').value = s.email_smtp_encryption || '';
        document.getElementById('email-smtp-username').value = s.email_smtp_username || '';
        document.getElementById('email-smtp-password').value = '';
        document.getElementById('email-smtp-password-status').textContent = s.email_smtp_password_set ? 'Wachtwoord is ingesteld.' : 'Nog geen SMTP-wachtwoord opgeslagen.';
        document.getElementById('email-from-name').value = s.email_from_name || '';
        document.getElementById('email-from-address').value = s.email_from_address || '';
        document.getElementById('email-reply-to-name').value = s.email_reply_to_name || '';
        document.getElementById('email-reply-to-address').value = s.email_reply_to_address || '';
        document.getElementById('email-verification-base-url').value = s.email_verification_link_base_url || '';
        document.getElementById('email-verification-ttl').value = s.email_verification_token_ttl_minutes || '';
        document.getElementById('email-send-welcome').value = boolToSelect(!!s.email_send_welcome);
        document.getElementById('email-send-verification').value = boolToSelect(!!s.email_send_verification);
        document.getElementById('email-send-notifications').value = boolToSelect(!!s.email_send_notifications);
        document.getElementById('payment-bank-name').value = s.payment_bank_name || '';
        document.getElementById('payment-bank-account').value = s.payment_bank_account || '';
        document.getElementById('payment-bank-account-name').value = s.payment_bank_account_name || '';

        const testTemplateSelect = document.getElementById('test-template-key');
        if (testTemplateSelect) {
            testTemplateSelect.innerHTML = currentTemplates.map((tpl) => (
                `<option value="${escapeHtml(tpl.template_key || '')}">${escapeHtml(tpl.template_key || '')}</option>`
            )).join('');
            ensureOption('test-template-key', 'test_email');
            testTemplateSelect.value = Array.from(testTemplateSelect.options).some((opt) => opt.value === 'test_email')
                ? 'test_email'
                : (testTemplateSelect.options[0]?.value || 'test_email');
        }

        renderTemplates(currentTemplates);
        await loadEmailLogs();
    }

    async function saveEmailSettings(withTemplates = false) {
        const statusEl = document.getElementById('email-status');
        const payload = {
            email_enabled: valAsBool('email-enabled'),
            email_mailer: document.getElementById('email-mailer').value || null,
            email_smtp_host: document.getElementById('email-smtp-host').value.trim() || null,
            email_smtp_port: document.getElementById('email-smtp-port').value === '' ? null : Number(document.getElementById('email-smtp-port').value),
            email_smtp_encryption: document.getElementById('email-smtp-encryption').value || null,
            email_smtp_username: document.getElementById('email-smtp-username').value.trim() || null,
            email_smtp_password: document.getElementById('email-smtp-password').value.trim() || null,
            email_from_name: document.getElementById('email-from-name').value.trim() || null,
            email_from_address: document.getElementById('email-from-address').value.trim() || null,
            email_reply_to_name: document.getElementById('email-reply-to-name').value.trim() || null,
            email_reply_to_address: document.getElementById('email-reply-to-address').value.trim() || null,
            email_verification_link_base_url: document.getElementById('email-verification-base-url').value.trim() || null,
            email_verification_token_ttl_minutes: document.getElementById('email-verification-ttl').value === '' ? null : Number(document.getElementById('email-verification-ttl').value),
            email_send_welcome: valAsBool('email-send-welcome'),
            email_send_verification: valAsBool('email-send-verification'),
            email_send_notifications: valAsBool('email-send-notifications'),
            payment_bank_name: document.getElementById('payment-bank-name').value.trim() || null,
            payment_bank_account: document.getElementById('payment-bank-account').value.trim() || null,
            payment_bank_account_name: document.getElementById('payment-bank-account-name').value.trim() || null
        };
        if (withTemplates) {
            payload.templates = readTemplatesFromForm();
        }

        const res = await AdminApp.api('/api/admin/email-settings', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, data.message || 'Opslaan mislukt.', 'error');
            return;
        }
        AdminApp.setStatus(statusEl, withTemplates ? 'Email instellingen + templates opgeslagen.' : 'Email instellingen opgeslagen.', 'success');
        await loadEmailSettings();
    }

    async function sendTestEmail() {
        const statusEl = document.getElementById('email-test-status');
        const payload = {
            to_email: document.getElementById('test-to-email').value.trim(),
            template_key: document.getElementById('test-template-key').value || 'test_email'
        };
        if (!payload.to_email) {
            AdminApp.setStatus(statusEl, 'Vul een ontvanger in.', 'error');
            return;
        }
        const res = await AdminApp.api('/api/admin/email-settings/test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, data.message || 'Testmail versturen mislukt.', 'error');
            return;
        }
        AdminApp.setStatus(statusEl, data.message || 'Testmail verstuurd.', 'success');
        await loadEmailLogs();
    }

    async function loadEmailLogs() {
        const logsEl = document.getElementById('email-logs');
        const res = await AdminApp.api('/api/admin/email-settings/logs?limit=15');
        const data = await res.json();
        if (!res.ok) {
            logsEl.textContent = data.message || 'Email logs laden mislukt.';
            return;
        }
        const logs = Array.isArray(data.logs) ? data.logs : [];
        if (!logs.length) {
            logsEl.innerHTML = '<div class="status">Nog geen email logs.</div>';
            return;
        }
        logsEl.innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>To</th>
                        <th>Template</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    ${logs.map((log) => `
                        <tr>
                            <td>${log.id}</td>
                            <td>${escapeHtml(log.to_email || '')}</td>
                            <td>${escapeHtml(log.template_key || '-')}</td>
                            <td>${escapeHtml(log.status || '-')}</td>
                            <td>${escapeHtml(log.created_at || '-')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    function escapeHtml(value) {
        const str = String(value ?? '');
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    document.getElementById('btn-email-save').addEventListener('click', () => saveEmailSettings(false));
    document.getElementById('btn-template-save').addEventListener('click', () => saveEmailSettings(true));
    document.getElementById('btn-email-test').addEventListener('click', sendTestEmail);

    loadEmailSettings();
</script>
@endsection
