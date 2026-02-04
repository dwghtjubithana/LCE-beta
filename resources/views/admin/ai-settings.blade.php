@extends('admin.layout')

@section('title', 'AI-instellingen')
@php($active = 'ai-settings')

@section('content')
<div class="page-header">
    <div>
        <h2>AI-instellingen</h2>
        <p>Beheer alle AI‑instellingen en modellen voor documentanalyse.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/system'">Terug naar systeemstatus</button>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Gemini-status</h3>
    <p class="status">Controleer of de AI‑verbinding werkt.</p>
    <div id="gemini" class="status">Laden...</div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn secondary" id="btn-gemini">Test Gemini</button>
    </div>
</div>

<div class="card">
    <div class="form-stack">
        <div class="form-field">
            <label for="ai-key">Gemini API Key</label>
            <input class="input" id="ai-key" type="password" placeholder="Paste API key">
            <p class="status">Gebruikt om Gemini‑requests te authenticeren.</p>
        </div>
        <div class="form-field">
            <label for="ai-model-validation">Validatiemodel</label>
            <input class="input" id="ai-model-validation" placeholder="gemini-2.5-flash-preview-09-2025">
            <p class="status">Model voor compliance‑validatie.</p>
        </div>
        <div class="form-field">
            <label for="ai-model-summary">Samenvattingsmodel</label>
            <input class="input" id="ai-model-summary" placeholder="gemini-2.5-flash-preview-09-2025">
            <p class="status">Model voor samenvatting en advies.</p>
        </div>
        <div class="form-field">
            <label for="ai-temperature">Temperature</label>
            <input class="input" id="ai-temperature" type="number" step="0.1" min="0" max="1" placeholder="0.2">
            <p class="status">Lager = consistenter, hoger = creatiever.</p>
        </div>
        <div class="form-field">
            <label for="ai-top-p">Top‑P</label>
            <input class="input" id="ai-top-p" type="number" step="0.05" min="0" max="1" placeholder="0.9">
            <p class="status">Bepaalt variatie in antwoorden (0–1).</p>
        </div>
        <div class="form-field">
            <label for="ai-max-tokens">Maximaal aantal tokens</label>
            <input class="input" id="ai-max-tokens" type="number" min="1" max="8192" placeholder="2048">
            <p class="status">Limiteert de lengte van de AI‑response.</p>
        </div>
        <div class="form-field">
            <label for="ai-debug">Gemini debug volledig</label>
            <select id="ai-debug">
                <option value="">Select</option>
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
            <p class="status">Sla volledige AI‑responses op voor debugging.</p>
        </div>
        <div class="form-field">
            <label for="ai-require-gemini">Gemini verplicht</label>
            <select id="ai-require-gemini">
                <option value="">Select</option>
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
            <p class="status">Indien aan: documenten gaan naar handmatige review wanneer Gemini niet beschikbaar is.</p>
        </div>
        <div class="form-field">
            <label for="ai-require-ocr">OCR verplicht</label>
            <select id="ai-require-ocr">
                <option value="">Select</option>
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
            <p class="status">Indien aan: lage OCR‑kwaliteit dwingt handmatige review af.</p>
        </div>
        <div class="form-field">
            <label for="ai-min-ocr">Min. OCR-betrouwbaarheid</label>
            <input class="input" id="ai-min-ocr" type="number" min="0" max="100" placeholder="70">
            <p class="status">Minimum OCR‑betrouwbaarheid (0–100).</p>
        </div>
        <div class="form-field">
            <label for="ai-allow-image-only">Alleen beeldanalyse toestaan</label>
            <select id="ai-allow-image-only">
                <option value="">Select</option>
                <option value="1">Enabled</option>
                <option value="0">Disabled</option>
            </select>
            <p class="status">Indien uit: Gemini draait alleen wanneer OCR‑tekst bestaat.</p>
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-ai-save">AI-instellingen opslaan</button>
    </div>
    <div class="status" id="ai-status"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    async function testGemini() {
        const target = document.getElementById('gemini');
        try {
            const res = await AdminApp.api('/api/admin/gemini/health');
            const data = await res.json();
            if (!res.ok) {
                target.textContent = data.message || 'Failed to test Gemini.';
                return;
            }
            const result = data?.result || {};
            const status = String(result.status || '').toLowerCase();
            const label = status === 'ok'
                ? '<span style="color:#16a34a;font-weight:600;">Connected</span>'
                : '<span style="color:#ef4444;font-weight:600;">Not connected</span>';
            target.innerHTML = `
                <div class="status">Status: ${label}</div>
                <div class="status">Message: ${result.message || '—'}</div>
            `;
        } catch (err) {
            target.textContent = 'Failed to test Gemini.';
        }
    }

    async function loadAiSettings() {
        const statusEl = document.getElementById('ai-status');
        const res = await AdminApp.api('/api/admin/ai-settings');
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, data.message || 'Failed to load AI settings.', 'error');
            return;
        }
        const s = data.settings || {};
        document.getElementById('ai-key').value = s.gemini_api_key ? '********' : '';
        document.getElementById('ai-model-validation').value = s.gemini_model_validation || '';
        document.getElementById('ai-model-summary').value = s.gemini_model_summary || '';
        document.getElementById('ai-debug').value = s.gemini_debug_full ?? '';
        document.getElementById('ai-temperature').value = s.gemini_temperature ?? '';
        document.getElementById('ai-top-p').value = s.gemini_top_p ?? '';
        document.getElementById('ai-max-tokens').value = s.gemini_max_output_tokens ?? '';
        document.getElementById('ai-require-gemini').value = s.ai_require_gemini ?? '';
        document.getElementById('ai-require-ocr').value = s.ai_require_ocr ?? '';
        document.getElementById('ai-min-ocr').value = s.ai_min_ocr_confidence ?? '';
        document.getElementById('ai-allow-image-only').value = s.ai_allow_image_only ?? '';
    }

    async function saveAiSettings() {
        const statusEl = document.getElementById('ai-status');
        const payload = {
            gemini_api_key: document.getElementById('ai-key').value.trim() || null,
            gemini_model_validation: document.getElementById('ai-model-validation').value.trim() || null,
            gemini_model_summary: document.getElementById('ai-model-summary').value.trim() || null,
            gemini_debug_full: document.getElementById('ai-debug').value === '' ? null : document.getElementById('ai-debug').value === '1',
            gemini_temperature: document.getElementById('ai-temperature').value === '' ? null : Number(document.getElementById('ai-temperature').value),
            gemini_top_p: document.getElementById('ai-top-p').value === '' ? null : Number(document.getElementById('ai-top-p').value),
            gemini_max_output_tokens: document.getElementById('ai-max-tokens').value === '' ? null : Number(document.getElementById('ai-max-tokens').value),
            ai_require_gemini: document.getElementById('ai-require-gemini').value === '' ? null : document.getElementById('ai-require-gemini').value === '1',
            ai_require_ocr: document.getElementById('ai-require-ocr').value === '' ? null : document.getElementById('ai-require-ocr').value === '1',
            ai_min_ocr_confidence: document.getElementById('ai-min-ocr').value === '' ? null : Number(document.getElementById('ai-min-ocr').value),
            ai_allow_image_only: document.getElementById('ai-allow-image-only').value === '' ? null : document.getElementById('ai-allow-image-only').value === '1'
        };
        const res = await AdminApp.api('/api/admin/ai-settings', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok) {
            AdminApp.setStatus(statusEl, data.message || 'Failed to save AI settings.', 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'AI settings saved.', 'success');
    }

    document.getElementById('btn-ai-save').addEventListener('click', saveAiSettings);
    document.getElementById('btn-gemini').addEventListener('click', testGemini);
    testGemini();
    loadAiSettings();
</script>
@endsection
