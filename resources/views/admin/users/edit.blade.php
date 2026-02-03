@extends('admin.layout')

@section('title', 'Edit User')
@php($active = 'users')

@section('content')
<div class="page-header">
    <div>
        <h2>Edit User</h2>
        <p>Update access level, plan, and status.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/users/{{ $id }}'">Cancel</button>
    </div>
</div>

<div class="card">
    <div class="form-stack">
        <div class="form-field">
            <label for="edit-name">Name</label>
            <input class="input" id="edit-name" placeholder="Name" disabled>
        </div>
        <div class="form-field">
            <label for="edit-email">Email</label>
            <input class="input" id="edit-email" placeholder="Email" disabled>
        </div>
        <div class="form-field">
            <label for="edit-phone">Phone</label>
            <input class="input" id="edit-phone" placeholder="Phone" disabled>
        </div>
        <div class="form-field">
            <label for="edit-role">Role</label>
            <select id="edit-role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-field">
            <label for="edit-status">Status</label>
            <select id="edit-status">
                <option value="ACTIVE">Active</option>
                <option value="SUSPENDED">Suspended</option>
            </select>
        </div>
        <div class="form-field">
            <label for="edit-plan">Plan</label>
            <select id="edit-plan">
                <option value="FREE">Free</option>
                <option value="PRO">Pro</option>
                <option value="BUSINESS">Business</option>
            </select>
        </div>
        <div class="form-field">
            <label for="edit-plan-status">Plan status</label>
            <select id="edit-plan-status">
                <option value="ACTIVE">Active</option>
                <option value="PENDING_PAYMENT">Pending payment</option>
                <option value="EXPIRED">Expired</option>
            </select>
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-save">Save changes</button>
    </div>
    <div class="status" id="edit-status-msg"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const userId = {{ $id }};

    async function loadUser() {
        const statusEl = document.getElementById('edit-status-msg');
        const res = await AdminApp.api(`/api/admin/users/${userId}`);
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        const user = data.user;
        document.getElementById('edit-name').value = user.username || '';
        document.getElementById('edit-email').value = user.email || '';
        document.getElementById('edit-phone').value = user.phone || '';
        document.getElementById('edit-role').value = user.app_role || 'user';
        document.getElementById('edit-status').value = user.status || 'ACTIVE';
        document.getElementById('edit-plan').value = user.plan || 'FREE';
        document.getElementById('edit-plan-status').value = user.plan_status || 'ACTIVE';
    }

    document.getElementById('btn-save').addEventListener('click', async () => {
        const payload = {
            app_role: document.getElementById('edit-role').value,
            status: document.getElementById('edit-status').value,
            plan: document.getElementById('edit-plan').value,
            plan_status: document.getElementById('edit-plan-status').value
        };
        const res = await AdminApp.api(`/api/admin/users/${userId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const statusEl = document.getElementById('edit-status-msg');
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'User updated successfully.', 'success');
    });

    loadUser();
</script>
@endsection
