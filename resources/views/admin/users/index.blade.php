@extends('admin.layout')

@section('title', 'Users')
@php($active = 'users')

@section('content')
<div class="page-header">
    <div>
        <h2>Users</h2>
        <p>Manage access, plans, and statuses.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/users/new'">Create user</button>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Search name, email, phone">
        <select id="filter-role">
            <option value="">All roles</option>
            <option value="admin">Admin</option>
            <option value="user">User</option>
        </select>
        <select id="filter-status">
            <option value="">All statuses</option>
            <option value="ACTIVE">Active</option>
            <option value="SUSPENDED">Suspended</option>
        </select>
        <select id="filter-plan">
            <option value="">All plans</option>
            <option value="FREE">Free</option>
            <option value="PRO">Pro</option>
            <option value="BUSINESS">Business</option>
        </select>
        <select id="filter-limit">
            <option value="10">10</option>
            <option value="20" selected>20</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-refresh">Apply filters</button>
    </div>
</div>

<div class="card">
    <div id="users-table"></div>
    <div class="pagination" style="margin-top:12px;">
        <button class="btn secondary" id="btn-prev">Prev</button>
        <span class="status" id="page-info">Page 1/1</span>
        <button class="btn secondary" id="btn-next">Next</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    let page = 1;
    let meta = { page: 1, total_pages: 1 };

    function renderTable(rows) {
        if (!rows.length) {
            document.getElementById('users-table').innerHTML = '<div class="status">No users found.</div>';
            return;
        }
        const body = rows.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${user.username || '-'}</td>
                <td>${user.email || '-'}</td>
                <td>${user.phone || '-'}</td>
                <td><span class="badge">${user.app_role || 'user'}</span></td>
                <td>${user.plan || 'FREE'}</td>
                <td>${user.status || 'ACTIVE'}</td>
                <td class="actions">
                    <a href="/admin/users/${user.id}" class="btn secondary">View</a>
                    <a href="/admin/users/${user.id}/edit" class="btn secondary">Edit</a>
                </td>
            </tr>
        `).join('');
        document.getElementById('users-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    async function loadUsers() {
        const params = new URLSearchParams();
        const search = document.getElementById('filter-search').value.trim();
        const role = document.getElementById('filter-role').value;
        const status = document.getElementById('filter-status').value;
        const plan = document.getElementById('filter-plan').value;
        const limit = document.getElementById('filter-limit').value;
        if (search) params.append('search', search);
        if (role) params.append('role', role);
        if (status) params.append('status', status);
        if (plan) params.append('plan', plan);
        params.append('limit', limit);
        params.append('page', String(page));

        const res = await AdminApp.api(`/api/admin/users?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('users-table').innerHTML = `<div class="status">${data.message || 'Failed to load users.'}</div>`;
            return;
        }
        renderTable(data.users || []);
        meta = data.meta || meta;
        document.getElementById('page-info').textContent = `Page ${meta.page || 1}/${meta.total_pages || 1}`;
        document.getElementById('btn-prev').disabled = (meta.page || 1) <= 1;
        document.getElementById('btn-next').disabled = (meta.page || 1) >= (meta.total_pages || 1);
    }

    document.getElementById('btn-refresh').addEventListener('click', () => {
        page = 1;
        loadUsers();
    });
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (page > 1) {
            page -= 1;
            loadUsers();
        }
    });
    document.getElementById('btn-next').addEventListener('click', () => {
        page += 1;
        loadUsers();
    });

    loadUsers();
</script>
@endsection
