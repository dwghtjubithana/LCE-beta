@extends('admin.layout')

@section('title', 'Notifications')
@php($active = 'notifications')

@section('content')
<div class="page-header">
    <div>
        <h2>Notifications</h2>
        <p>Track delivery status and resend when needed.</p>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Search type or user id">
        <select id="filter-status">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="sent">Sent</option>
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
    <div id="notifications-table"></div>
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
            document.getElementById('notifications-table').innerHTML = '<div class="status">No notifications found.</div>';
            return;
        }
        const body = rows.map(n => `
            <tr>
                <td>${n.id}</td>
                <td>${n.type || '-'}</td>
                <td>${n.channel || '-'}</td>
                <td>${n.user_id || '-'}</td>
                <td>${n.sent_at ? 'Sent' : 'Pending'}</td>
                <td class="actions">
                    <a href="/admin/notifications/${n.id}" class="btn secondary">View</a>
                </td>
            </tr>
        `).join('');
        document.getElementById('notifications-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Channel</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    async function loadNotifications() {
        const params = new URLSearchParams();
        const search = document.getElementById('filter-search').value.trim();
        const status = document.getElementById('filter-status').value;
        const limit = document.getElementById('filter-limit').value;
        if (search) params.append('search', search);
        if (status) params.append('status', status);
        params.append('limit', limit);
        params.append('page', String(page));

        const res = await AdminApp.api(`/api/admin/notifications?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('notifications-table').innerHTML = `<div class="status">${data.message || 'Failed to load notifications.'}</div>`;
            return;
        }
        renderTable(data.notifications || []);
        meta = data.meta || meta;
        document.getElementById('page-info').textContent = `Page ${meta.page || 1}/${meta.total_pages || 1}`;
        document.getElementById('btn-prev').disabled = (meta.page || 1) <= 1;
        document.getElementById('btn-next').disabled = (meta.page || 1) >= (meta.total_pages || 1);
    }

    document.getElementById('btn-refresh').addEventListener('click', () => {
        page = 1;
        loadNotifications();
    });
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (page > 1) {
            page -= 1;
            loadNotifications();
        }
    });
    document.getElementById('btn-next').addEventListener('click', () => {
        page += 1;
        loadNotifications();
    });

    loadNotifications();
</script>
@endsection
