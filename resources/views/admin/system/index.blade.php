@extends('admin.layout')

@section('title', 'Systeemstatus')
@php($active = 'system')

@section('content')
<div class="page-header">
    <div>
        <h2>Systeemstatus & AI</h2>
        <p>Bekijk status, metrics en beheer AI‑instellingen.</p>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3 style="margin-top:0;">Status</h3>
        <p class="status">API‑status en kernservices.</p>
        <div id="health" class="status">Loading...</div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Statistieken</h3>
        <p class="status">Overzicht van documenten en gebruikers.</p>
        <div id="metrics" class="status">Loading...</div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Gemini-status</h3>
        <p class="status">Controleer of de AI‑verbinding werkt.</p>
        <div id="gemini" class="status">Laden...</div>
        <div class="actions" style="margin-top:12px;">
            <button class="btn secondary" id="btn-gemini">Test Gemini</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    function formatLabel(key) {
        return key
            .replace(/_/g, ' ')
            .replace(/([a-z])([A-Z])/g, '$1 $2')
            .replace(/\b\w/g, (c) => c.toUpperCase());
    }
    function formatValue(value) {
        if (value === null || value === undefined) return '—';
        if (typeof value === 'object') return `<span class="muted">${JSON.stringify(value)}</span>`;
        return String(value);
    }
    function renderKeyValue(targetId, data) {
        const target = document.getElementById(targetId);
        if (!target) return;
        if (!data || typeof data !== 'object') {
            target.textContent = 'No data available.';
            return;
        }
        const rows = Object.entries(data).map(([key, value]) => {
            return `<tr><td>${formatLabel(key)}</td><td>${formatValue(value)}</td></tr>`;
        }).join('');
        target.innerHTML = `
            <table class="table">
                <thead><tr><th>Field</th><th>Value</th></tr></thead>
                <tbody>${rows || '<tr><td colspan="2">No data</td></tr>'}</tbody>
            </table>
        `;
    }

    async function loadHealth() {
        const res = await AdminApp.api('/api/admin/health');
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('health').textContent = data.message || 'Failed to load health.';
            return;
        }
        renderKeyValue('health', data.health || {});
    }

    async function loadMetrics() {
        const res = await AdminApp.api('/api/admin/metrics');
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('metrics').textContent = data.message || 'Failed to load metrics.';
            return;
        }
        renderKeyValue('metrics', data.metrics || {});
    }

    async function testGemini() {
        const res = await AdminApp.api('/api/admin/gemini/health');
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('gemini').textContent = data.message || 'Failed to test Gemini.';
            return;
        }
        const result = data?.result || {};
        const status = String(result.status || '').toLowerCase();
        const label = status === 'ok'
            ? '<span style="color:#16a34a;font-weight:600;">Connected</span>'
            : '<span style="color:#ef4444;font-weight:600;">Not connected</span>';
        renderKeyValue('gemini', {
            status: status || 'unknown',
            connection: label,
            message: result.message || null,
        });
    }

    document.getElementById('btn-gemini').addEventListener('click', testGemini);
    loadHealth();
    loadMetrics();
    testGemini();
</script>
@endsection
