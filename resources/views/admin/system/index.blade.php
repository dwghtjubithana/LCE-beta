@extends('admin.layout')

@section('title', 'Systeemstatus')
@php($active = 'system')

@section('content')
@include('admin.partials.settings-subnav')

<div class="page-header">
    <div>
        <h2>Systeemstatus & Documentanalyse</h2>
        <p>Bekijk status, metrics en beheer analyse-instellingen.</p>
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
        <p class="status">Controleer of de analyseverbinding werkt.</p>
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

    function escapeHtml(value) {
        const str = String(value ?? '');
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderStatTiles(stats) {
        return `
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;">
                ${stats.map((item) => `
                    <div style="border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;background:#fff;">
                        <div class="status" style="font-size:11px;text-transform:uppercase;letter-spacing:.08em;">${escapeHtml(item.label)}</div>
                        <div style="font-size:22px;font-weight:800;line-height:1.2;color:#0f172a;">${escapeHtml(item.value)}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function formatDuration(seconds) {
        if (seconds === null || seconds === undefined || Number.isNaN(Number(seconds))) return '—';
        const s = Number(seconds);
        if (s < 60) return `${s}s`;
        const m = Math.floor(s / 60);
        const rs = s % 60;
        return rs ? `${m}m ${rs}s` : `${m}m`;
    }

    function renderHealth(health) {
        const target = document.getElementById('health');
        if (!target) return;
        if (!health || typeof health !== 'object') {
            target.textContent = 'Geen statusdata beschikbaar.';
            return;
        }

        const env = String(health.app_env || 'unknown').toUpperCase();
        const envColor = env === 'PRODUCTION' ? '#16a34a' : '#f59e0b';

        target.innerHTML = `
            ${renderStatTiles([
                { label: 'Environment', value: env },
                { label: 'Queue', value: health.queue_connection || '—' },
                { label: 'Laravel', value: health.app_version || '—' },
            ])}
            <div style="margin-top:12px;display:grid;gap:8px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="display:inline-flex;padding:3px 9px;border-radius:999px;background:${envColor}22;color:${envColor};font-size:12px;font-weight:700;">${escapeHtml(env)}</span>
                    <span class="status">Actieve omgeving</span>
                </div>
                <div class="status">Laatste tender import: <strong>${escapeHtml(AdminApp.formatDateTime(health.last_tender_import_at))}</strong></div>
                <div class="status">Laatste notificaties verstuurd: <strong>${escapeHtml(AdminApp.formatDateTime(health.last_notifications_sent_at))}</strong></div>
            </div>
        `;
    }

    function renderStatusDistribution(statusMap) {
        const entries = Object.entries(statusMap || {});
        if (!entries.length) {
            return '<div class="status">Nog geen documenten met status.</div>';
        }

        const total = entries.reduce((sum, [, count]) => sum + Number(count || 0), 0) || 1;
        return `
            <div style="display:grid;gap:8px;">
                ${entries.map(([status, count]) => {
                    const safeCount = Number(count || 0);
                    const pct = Math.max(0, Math.min(100, Math.round((safeCount / total) * 100)));
                    return `
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                <span style="font-size:12px;font-weight:700;color:#334155;">${escapeHtml(status)}</span>
                                <span style="font-size:12px;color:#64748b;">${safeCount} (${pct}%)</span>
                            </div>
                            <div style="height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;">
                                <div style="height:8px;width:${pct}%;background:#0ea5a4;"></div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    function renderMetrics(metrics) {
        const target = document.getElementById('metrics');
        if (!target) return;
        if (!metrics || typeof metrics !== 'object') {
            target.textContent = 'Geen metrics beschikbaar.';
            return;
        }

        target.innerHTML = `
            ${renderStatTiles([
                { label: 'Users', value: metrics.total_users ?? 0 },
                { label: 'Companies', value: metrics.total_companies ?? 0 },
                { label: 'Documents', value: metrics.total_documents ?? 0 },
                { label: 'Gem. verwerking', value: formatDuration(metrics.avg_processing_seconds) },
            ])}
            <div style="margin-top:12px;">
                <div class="status" style="margin-bottom:6px;">Documenten per status</div>
                ${renderStatusDistribution(metrics.documents_by_status)}
            </div>
        `;
    }

    function renderGemini(result) {
        const target = document.getElementById('gemini');
        if (!target) return;
        const status = String(result?.status || '').toLowerCase();
        const isOk = status === 'ok';
        const color = isOk ? '#16a34a' : '#ef4444';
        const label = isOk ? 'Connected' : 'Not connected';
        const message = result?.message || '—';

        target.innerHTML = `
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <span style="width:10px;height:10px;border-radius:999px;background:${color};display:inline-block;"></span>
                <strong style="color:${color};">${label}</strong>
            </div>
            <div class="status">Status: ${escapeHtml(status || 'unknown')}</div>
            <div class="status">Melding: ${escapeHtml(message)}</div>
        `;
    }

    async function loadHealth() {
        const res = await AdminApp.api('/api/admin/health');
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('health').textContent = data.message || 'Failed to load health.';
            return;
        }
        renderHealth(data.health || {});
    }

    async function loadMetrics() {
        const res = await AdminApp.api('/api/admin/metrics');
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('metrics').textContent = data.message || 'Failed to load metrics.';
            return;
        }
        renderMetrics(data.metrics || {});
    }

    async function testGemini() {
        const res = await AdminApp.api('/api/admin/gemini/health');
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('gemini').textContent = data.message || 'Failed to test Gemini.';
            return;
        }
        renderGemini(data?.result || {});
    }

    document.getElementById('btn-gemini').addEventListener('click', testGemini);
    loadHealth();
    loadMetrics();
    testGemini();
</script>
@endsection
