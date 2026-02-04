@extends('admin.layout')

@section('title', 'Payment Proofs')
@php($active = 'payment-proofs')

@section('content')
<div class="page-header">
    <div>
        <h2>Payment Proofs</h2>
        <p>Review manual payment proofs and upgrade plans.</p>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Search by user id or status">
        <select id="filter-status">
            <option value="">All</option>
            <option value="PENDING" selected>Pending</option>
            <option value="APPROVED">Approved</option>
            <option value="REJECTED">Rejected</option>
        </select>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-refresh">Apply filters</button>
    </div>
</div>

<div class="card">
    <div id="proofs-table"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    function renderTable(rows) {
        if (!rows.length) {
            document.getElementById('proofs-table').innerHTML = '<div class="status">No payment proofs found.</div>';
            return;
        }
        const body = rows.map(p => `
            <tr>
                <td>${p.id}</td>
                <td>${p.user_id || '-'}</td>
                <td>${p.company_id || '-'}</td>
                <td>${p.status || '-'}</td>
                <td>${AdminApp.formatDateTime(p.submitted_at)}</td>
                <td class="actions">
                    <button class="btn secondary" data-action="approve" data-id="${p.id}">Approve</button>
                    <button class="btn secondary" data-action="reject" data-id="${p.id}">Reject</button>
                </td>
            </tr>
        `).join('');
        document.getElementById('proofs-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    async function loadProofs() {
        const status = document.getElementById('filter-status').value;
        const search = document.getElementById('filter-search').value.trim();

        const res = await AdminApp.api('/api/admin/payment-proofs');
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('proofs-table').innerHTML = `<div class="status">${data.message || 'Failed to load proofs.'}</div>`;
            return;
        }
        let rows = data.payment_proofs || [];
        if (status) rows = rows.filter(r => (r.status || '').toUpperCase() === status);
        if (search) rows = rows.filter(r => String(r.user_id || '').includes(search) || (r.status || '').toUpperCase().includes(search.toUpperCase()));
        renderTable(rows);
    }

    async function handleAction(id, action) {
        const res = await AdminApp.api(`/api/admin/payment-proofs/${id}/${action}`, { method: 'POST' });
        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Action failed');
            return;
        }
        loadProofs();
    }

    document.getElementById('btn-refresh').addEventListener('click', () => loadProofs());
    document.getElementById('proofs-table').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (id && action) handleAction(id, action);
    });

    loadProofs();
</script>
@endsection
