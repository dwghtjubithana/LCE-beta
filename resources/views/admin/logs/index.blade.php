@extends('admin.layout')

@section('title', 'Audit Logs')
@php($active = 'logs')

@section('content')
<div class="page-header">
    <div>
        <h2>Audit Logs</h2>
        <p>Track actions taken by admins and automated jobs.</p>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Search action or target">
        <select id="filter-limit">
            <option value="20">20</option>
            <option value="50" selected>50</option>
            <option value="100">100</option>
        </select>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-refresh">Apply filters</button>
    </div>
</div>

<div class="card">
    <div id="logs-table"></div>
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
            document.getElementById('logs-table').innerHTML = '<div class="status">No logs found.</div>';
            return;
        }
        const body = rows.map(log => `
            <tr>
                <td>${log.id}</td>
                <td>${log.action || '-'}</td>
                <td>${log.target_type || '-'}</td>
                <td>${log.target_id || '-'}</td>
                <td>${log.created_at || '-'}</td>
            </tr>
        `).join('');
        document.getElementById('logs-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Target ID</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    async function loadLogs() {
        const params = new URLSearchParams();
        const search = document.getElementById('filter-search').value.trim();
        const limit = document.getElementById('filter-limit').value;
        if (search) params.append('search', search);
        params.append('limit', limit);
        params.append('page', String(page));

        const res = await AdminApp.api(`/api/admin/audit-logs?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('logs-table').innerHTML = `<div class="status">${data.message || 'Failed to load logs.'}</div>`;
            return;
        }
        renderTable(data.logs || []);
        meta = data.meta || meta;
        document.getElementById('page-info').textContent = `Page ${meta.page || 1}/${meta.total_pages || 1}`;
        document.getElementById('btn-prev').disabled = (meta.page || 1) <= 1;
        document.getElementById('btn-next').disabled = (meta.page || 1) >= (meta.total_pages || 1);
    }

    document.getElementById('btn-refresh').addEventListener('click', () => {
        page = 1;
        loadLogs();
    });
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (page > 1) {
            page -= 1;
            loadLogs();
        }
    });
    document.getElementById('btn-next').addEventListener('click', () => {
        page += 1;
        loadLogs();
    });

    loadLogs();
</script>
@endsection
