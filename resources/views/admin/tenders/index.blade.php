@extends('admin.layout')

@section('title', 'Tenders')
@php($active = 'tenders')

@section('content')
<div class="page-header">
    <div>
        <h2>Tenders</h2>
        <p>Manage tender listings and visibility rules.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/tenders/new'">Create tender</button>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Search title or client">
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
    <div id="tenders-table"></div>
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
            document.getElementById('tenders-table').innerHTML = '<div class="status">No tenders found.</div>';
            return;
        }
        const body = rows.map(tender => `
            <tr>
                <td>${tender.id}</td>
                <td>${tender.title || tender.project || '-'}</td>
                <td>${tender.client || '-'}</td>
                <td>${tender.date || '-'}</td>
                <td class="actions">
                    <a href="/admin/tenders/${tender.id}" class="btn secondary">View</a>
                </td>
            </tr>
        `).join('');
        document.getElementById('tenders-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    async function loadTenders() {
        const params = new URLSearchParams();
        const search = document.getElementById('filter-search').value.trim();
        const limit = document.getElementById('filter-limit').value;
        if (search) params.append('search', search);
        params.append('limit', limit);
        params.append('page', String(page));

        const res = await AdminApp.api(`/api/admin/tenders?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('tenders-table').innerHTML = `<div class="status">${data.message || 'Failed to load tenders.'}</div>`;
            return;
        }
        renderTable(data.tenders || []);
        meta = data.meta || meta;
        document.getElementById('page-info').textContent = `Page ${meta.page || 1}/${meta.total_pages || 1}`;
        document.getElementById('btn-prev').disabled = (meta.page || 1) <= 1;
        document.getElementById('btn-next').disabled = (meta.page || 1) >= (meta.total_pages || 1);
    }

    document.getElementById('btn-refresh').addEventListener('click', () => {
        page = 1;
        loadTenders();
    });
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (page > 1) {
            page -= 1;
            loadTenders();
        }
    });
    document.getElementById('btn-next').addEventListener('click', () => {
        page += 1;
        loadTenders();
    });

    loadTenders();
</script>
@endsection
