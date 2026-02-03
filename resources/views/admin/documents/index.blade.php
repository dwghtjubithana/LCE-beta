@extends('admin.layout')

@section('title', 'Documents')
@php($active = 'documents')

@section('content')
<div class="page-header">
    <div>
        <h2>Documents</h2>
        <p>Monitor uploaded documents and processing status.</p>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Search filename or category">
        <select id="filter-status">
            <option value="">All statuses</option>
            <option value="PROCESSING">Processing</option>
            <option value="VALID">Valid</option>
            <option value="INVALID">Invalid</option>
            <option value="EXPIRED">Expired</option>
            <option value="EXPIRING_SOON">Expiring Soon</option>
            <option value="MANUAL_REVIEW">Manual Review</option>
        </select>
        <select id="filter-category">
            <option value="">All categories</option>
            <option value="KKF Uittreksel">KKF Uittreksel</option>
            <option value="Vergunning">Vergunning</option>
            <option value="Belastingverklaring">Belastingverklaring</option>
            <option value="ID Bewijs">ID Bewijs</option>
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
    <div id="documents-table"></div>
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
            document.getElementById('documents-table').innerHTML = '<div class="status">No documents found.</div>';
            return;
        }
        const body = rows.map(doc => `
            <tr>
                <td>${doc.id}</td>
                <td>${doc.original_filename || '-'}</td>
                <td>${doc.category_selected || '-'}</td>
                <td>${doc.status || '-'}</td>
                <td>${doc.company_id || '-'}</td>
                <td class="actions">
                    <a href="/admin/documents/${doc.id}" class="btn secondary">View</a>
                </td>
            </tr>
        `).join('');
        document.getElementById('documents-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Filename</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    async function loadDocuments() {
        const params = new URLSearchParams();
        const search = document.getElementById('filter-search').value.trim();
        const status = document.getElementById('filter-status').value;
        const category = document.getElementById('filter-category').value;
        const limit = document.getElementById('filter-limit').value;
        if (search) params.append('search', search);
        if (status) params.append('status', status);
        if (category) params.append('category', category);
        params.append('limit', limit);
        params.append('page', String(page));

        const res = await AdminApp.api(`/api/admin/documents?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('documents-table').innerHTML = `<div class="status">${data.message || 'Failed to load documents.'}</div>`;
            return;
        }
        renderTable(data.documents || []);
        meta = data.meta || meta;
        document.getElementById('page-info').textContent = `Page ${meta.page || 1}/${meta.total_pages || 1}`;
        document.getElementById('btn-prev').disabled = (meta.page || 1) <= 1;
        document.getElementById('btn-next').disabled = (meta.page || 1) >= (meta.total_pages || 1);
    }

    document.getElementById('btn-refresh').addEventListener('click', () => {
        page = 1;
        loadDocuments();
    });
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (page > 1) {
            page -= 1;
            loadDocuments();
        }
    });
    document.getElementById('btn-next').addEventListener('click', () => {
        page += 1;
        loadDocuments();
    });

    loadDocuments();
</script>
@endsection
