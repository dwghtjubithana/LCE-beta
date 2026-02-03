@extends('admin.layout')

@section('title', 'Admin Dashboard')
@php($active = 'dashboard')

@section('content')
<div class="page-header">
    <div>
        <h2>Dashboard</h2>
        <p>Overview of the latest activity and system health.</p>
    </div>
    <div class="actions">
        <button class="btn" onclick="window.location.href='/admin/users'">Manage users</button>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3 style="margin-top:0;">Users</h3>
        <p class="status" id="kpi-users">Loading...</p>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Companies</h3>
        <p class="status" id="kpi-companies">Loading...</p>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Documents</h3>
        <p class="status" id="kpi-docs">Loading...</p>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Processing time</h3>
        <p class="status" id="kpi-processing">Loading...</p>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3 style="margin-top:0;">Recent activity</h3>
        <div class="status">View detailed audit logs in the Logs section.</div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">System health</h3>
        <div id="system-health" class="status">Loading...</div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    async function loadMetrics() {
        const res = await AdminApp.api('/api/admin/metrics');
        if (!res.ok) return;
        const data = await res.json();
        const metrics = data.metrics || {};
        document.getElementById('kpi-users').textContent = `${metrics.total_users || 0} total users`;
        document.getElementById('kpi-companies').textContent = `${metrics.total_companies || 0} companies`;
        document.getElementById('kpi-docs').textContent = `${metrics.total_documents || 0} documents`;
        document.getElementById('kpi-processing').textContent = `${metrics.avg_processing_seconds || 0}s average`;
    }

    async function loadHealth() {
        const res = await AdminApp.api('/api/admin/health');
        if (!res.ok) return;
        const data = await res.json();
        const health = data.health || {};
        document.getElementById('system-health').textContent =
            `Env ${health.app_env || '-'} · Queue ${health.queue_connection || '-'} · Version ${health.app_version || '-'}`;
    }

    loadMetrics();
    loadHealth();
</script>
@endsection
