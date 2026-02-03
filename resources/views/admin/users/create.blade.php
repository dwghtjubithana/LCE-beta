@extends('admin.layout')

@section('title', 'Create User')
@php($active = 'users')

@section('content')
<div class="page-header">
    <div>
        <h2>Create User</h2>
        <p>Add a new admin or user account.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/users'">Back to list</button>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="user-name" placeholder="Username">
        <input class="input" id="user-email" placeholder="Email (optional)">
        <input class="input" id="user-phone" placeholder="Phone (optional)">
        <input class="input" id="user-password" type="password" placeholder="Password">
        <select id="user-role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
        <select id="user-status">
            <option value="ACTIVE">Active</option>
            <option value="SUSPENDED">Suspended</option>
        </select>
        <select id="user-plan">
            <option value="FREE">Free</option>
            <option value="PRO">Pro</option>
            <option value="BUSINESS">Business</option>
        </select>
        <select id="user-plan-status">
            <option value="ACTIVE">Active</option>
            <option value="PENDING_PAYMENT">Pending payment</option>
            <option value="EXPIRED">Expired</option>
        </select>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-create">Create user</button>
    </div>
    <div class="status" id="create-status"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    document.getElementById('btn-create').addEventListener('click', async () => {
        const payload = {
        const statusEl = document.getElementById('create-status');
        const username = document.getElementById('user-name').value.trim();
        const email = document.getElementById('user-email').value.trim();
        const phone = document.getElementById('user-phone').value.trim();
        if (!username) {
            AdminApp.setStatus(statusEl, 'Username is required.', 'error');
            return;
        }
        if (!email && !phone) {
            AdminApp.setStatus(statusEl, 'Provide at least an email or phone number.', 'error');
            return;
        }
        const payload = {
            username,
            email: email || null,
            phone: document.getElementById('user-phone').value.trim() || null,
            password: document.getElementById('user-password').value,
            app_role: document.getElementById('user-role').value,
            status: document.getElementById('user-status').value,
            plan: document.getElementById('user-plan').value,
            plan_status: document.getElementById('user-plan-status').value,
        };
        const res = await AdminApp.api('/api/admin/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'User created successfully.', 'success');
        window.location.href = `/admin/users/${data.user.id}`;
    });
</script>
@endsection
