@extends('admin.layout')

@section('title', 'Aanbestedingen')
@php($active = 'tenders')

@section('content')
<div class="page-header">
    <div>
        <h2>Aanbestedingen</h2>
        <p>Beheer aanbestedingen en keuringen.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/tenders/new'">Nieuwe aanbesteding</button>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Zoek op titel of opdrachtgever">
        <select id="filter-status">
            <option value="">Alle statussen</option>
            <option value="PENDING">In afwachting</option>
            <option value="APPROVED">Goedgekeurd</option>
            <option value="REJECTED">Afgewezen</option>
        </select>
        <select id="filter-limit">
            <option value="10">10</option>
            <option value="20" selected>20</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-refresh">Filters toepassen</button>
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
            document.getElementById('tenders-table').innerHTML = '<div class="status">Geen aanbestedingen gevonden.</div>';
            return;
        }
        const body = rows.map(tender => {
            const status = (tender.status || 'APPROVED').toUpperCase();
            const statusLabel = status === 'PENDING' ? 'In afwachting' : (status === 'REJECTED' ? 'Afgewezen' : 'Goedgekeurd');
            const statusClass = status === 'PENDING' ? 'badge-warn' : (status === 'REJECTED' ? 'badge-danger' : 'badge-success');
            const reviewActions = status === 'PENDING'
                ? `<button class="btn secondary" data-approve="${tender.id}">Goedkeuren</button>
                   <button class="btn danger" data-reject="${tender.id}">Afwijzen</button>`
                : '';
            return `
            <tr>
                <td>${tender.id}</td>
                <td>${tender.title || tender.project || '-'}</td>
                <td>${tender.client || '-'}</td>
                <td>${AdminApp.formatDate(tender.date)}</td>
                <td><span class="badge ${statusClass}">${statusLabel}</span></td>
                <td class="actions">
                    <a href="/admin/tenders/${tender.id}" class="btn secondary">Bekijk</a>
                    ${reviewActions}
                </td>
            </tr>
        `}).join('');
        document.getElementById('tenders-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titel</th>
                        <th>Opdrachtgever</th>
                        <th>Datum</th>
                        <th>Status</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;

        document.querySelectorAll('[data-approve]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-approve');
                await updateTenderStatus(id, 'approve');
            });
        });
        document.querySelectorAll('[data-reject]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-reject');
                await updateTenderStatus(id, 'reject');
            });
        });
    }

    async function loadTenders() {
        const params = new URLSearchParams();
        const search = document.getElementById('filter-search').value.trim();
        const limit = document.getElementById('filter-limit').value;
        const status = document.getElementById('filter-status').value;
        if (search) params.append('search', search);
        if (status) params.append('status', status);
        params.append('limit', limit);
        params.append('page', String(page));

        const res = await AdminApp.api(`/api/admin/tenders?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('tenders-table').innerHTML = `<div class="status">${data.message || 'Aanbestedingen laden mislukt.'}</div>`;
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

    async function updateTenderStatus(id, action) {
        const res = await AdminApp.api(`/api/admin/tenders/${id}/${action}`, { method: 'POST' });
        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Status bijwerken mislukt.');
            return;
        }
        loadTenders();
    }

    loadTenders();
</script>
@endsection
