@extends('admin.layout')

@section('title', 'User Details')
@php($active = 'users')

@section('content')
<div class="page-header">
    <div>
        <h2>User Details</h2>
        <p>Profile, access level, and activity metadata.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/users'">Back to list</button>
        <button class="btn" id="btn-edit">Edit user</button>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3 style="margin-top:0;">Profile</h3>
        <div id="user-profile" class="status">Loading...</div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Account Status</h3>
        <div id="user-status" class="status">Loading...</div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Metadata</h3>
    <div id="user-meta" class="status">Loading...</div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const userId = {{ $id }};
    document.getElementById('btn-edit').addEventListener('click', () => {
        window.location.href = `/admin/users/${userId}/edit`;
    });

    async function loadUser() {
        const res = await AdminApp.api(`/api/admin/users/${userId}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('user-profile').textContent = data.message || 'Failed to load user.';
            return;
        }
        const user = data.user;
        document.getElementById('user-profile').innerHTML = `
            <div><strong>${user.username || '—'}</strong></div>
            <div class="status">Email: ${user.email || '—'}</div>
            <div class="status">Phone: ${user.phone || '—'}</div>
        `;
        document.getElementById('user-status').innerHTML = `
            <div class="status">Role: ${user.app_role || 'user'}</div>
            <div class="status">Plan: ${user.plan || 'FREE'} (${user.plan_status || 'ACTIVE'})</div>
            <div class="status">Status: ${user.status || 'ACTIVE'}</div>
        `;
        document.getElementById('user-meta').innerHTML = `
            <div class="status">User ID: ${user.id}</div>
            <div class="status">UUID: ${user.uuid || '—'}</div>
            <div class="status">Created at: ${AdminApp.formatDateTime(user.created_at)}</div>
            <div class="status">Updated at: ${AdminApp.formatDateTime(user.updated_at)}</div>
        `;
    }

    loadUser();
</script>
@endsection
