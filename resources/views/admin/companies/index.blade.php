@extends('admin.layout')

@section('title', 'Companies')
@php($active = 'companies')

@section('content')
<div class="page-header">
    <div>
        <h2>Companies</h2>
        <p>Browse and review company profiles.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/companies/new'">Create company</button>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Search company name or sector">
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
    <div id="companies-table"></div>
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
            document.getElementById('companies-table').innerHTML = '<div class="status">No companies found.</div>';
            return;
        }
        const body = rows.map(company => `
            <tr>
                <td>${company.id}</td>
                <td>${company.company_name || '-'}</td>
                <td>${company.sector || '-'}</td>
                <td>${company.current_score ?? 0}%</td>
                <td>${company.verification_level || 'unverified'}</td>
                <td class="actions">
                    <a href="/admin/companies/${company.id}" class="btn secondary">View</a>
                </td>
            </tr>
        `).join('');
        document.getElementById('companies-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company</th>
                        <th>Sector</th>
                        <th>Score</th>
                        <th>Verification</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    async function loadCompanies() {
        const params = new URLSearchParams();
        const search = document.getElementById('filter-search').value.trim();
        const limit = document.getElementById('filter-limit').value;
        if (search) params.append('search', search);
        params.append('limit', limit);
        params.append('page', String(page));

        const res = await AdminApp.api(`/api/admin/companies?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('companies-table').innerHTML = `<div class="status">${data.message || 'Failed to load companies.'}</div>`;
            return;
        }
        renderTable(data.companies || []);
        meta = data.meta || meta;
        document.getElementById('page-info').textContent = `Page ${meta.page || 1}/${meta.total_pages || 1}`;
        document.getElementById('btn-prev').disabled = (meta.page || 1) <= 1;
        document.getElementById('btn-next').disabled = (meta.page || 1) >= (meta.total_pages || 1);
    }

    document.getElementById('btn-refresh').addEventListener('click', () => {
        page = 1;
        loadCompanies();
    });
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (page > 1) {
            page -= 1;
            loadCompanies();
        }
    });
    document.getElementById('btn-next').addEventListener('click', () => {
        page += 1;
        loadCompanies();
    });

    loadCompanies();
</script>
@endsection
