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
            <select id="edit-plan"></select>
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
    let loadedPlans = [];

    async function loadPlans(selectedKey = null) {
        const planEl = document.getElementById('edit-plan');
        const res = await AdminApp.api('/api/admin/plans?include_inactive=1');
        const data = await AdminApp.readJson(res);
        loadedPlans = Array.isArray(data?.plans) ? data.plans : [];
        if (!res.ok || !loadedPlans.length) {
            planEl.innerHTML = `
                <option value="FREE">Free</option>
                <option value="PRO">Premium (PRO)</option>
                <option value="BUSINESS">Business</option>
                <option value="ENTERPRISE">Enterprise</option>
            `;
            planEl.value = selectedKey || 'FREE';
            return;
        }
        planEl.innerHTML = loadedPlans.map((plan) => (
            `<option value="${plan.plan_key}">${plan.plan_label || plan.plan_key} (${plan.plan_key})</option>`
        )).join('');
        if (selectedKey && Array.from(planEl.options).some((opt) => opt.value === selectedKey)) {
            planEl.value = selectedKey;
            return;
        }
        const defaultPlan = loadedPlans.find((p) => !!p.is_default) || loadedPlans[0];
        if (defaultPlan?.plan_key) {
            planEl.value = defaultPlan.plan_key;
        }
    }

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
        await loadPlans(user.plan || 'FREE');
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
