@extends('admin.layout')

@section('title', 'Compliance Rules')
@php($active = 'rules')

@section('content')
<div class="page-header">
    <div>
        <h2>Compliance Rules</h2>
        <p>Maintain validation rules and thresholds.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/compliance-rules/new'">Create rule</button>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="filter-search" placeholder="Search by document type">
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
    <div id="rules-table"></div>
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
            document.getElementById('rules-table').innerHTML = '<div class="status">No rules found.</div>';
            return;
        }
        const body = rows.map(rule => {
            const keywords = Array.isArray(rule.required_keywords) ? rule.required_keywords.join(', ') : '-';
            const constraints = rule.constraints || {};
            const expiry = typeof constraints.expiry_required === 'boolean'
                ? (constraints.expiry_required ? 'Yes' : 'No')
                : '-';
            const requiredFields = Array.isArray(constraints.required_fields)
                ? constraints.required_fields.join(', ')
                : '-';
            return `
            <tr>
                <td>${rule.id}</td>
                <td>${rule.document_type || '-'}</td>
                <td>${rule.max_age_months || '-'}</td>
                <td>${keywords || '-'}</td>
                <td>${expiry}</td>
                <td>${requiredFields}</td>
                <td class="actions">
                    <a href="/admin/compliance-rules/${rule.id}" class="btn secondary">View</a>
                    <a href="/admin/compliance-rules/${rule.id}/edit" class="btn secondary">Edit</a>
                </td>
            </tr>
        `;
        }).join('');
        document.getElementById('rules-table').innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Document Type</th>
                        <th>Max Age (months)</th>
                        <th>Keywords</th>
                        <th>Expiry Required</th>
                        <th>Required Fields</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    async function loadRules() {
        const params = new URLSearchParams();
        const search = document.getElementById('filter-search').value.trim();
        const limit = document.getElementById('filter-limit').value;
        if (search) params.append('search', search);
        params.append('limit', limit);
        params.append('page', String(page));

        const res = await AdminApp.api(`/api/admin/compliance-rules?${params.toString()}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('rules-table').innerHTML = `<div class="status">${data.message || 'Failed to load rules.'}</div>`;
            return;
        }
        renderTable(data.rules || []);
        meta = data.meta || meta;
        document.getElementById('page-info').textContent = `Page ${meta.page || 1}/${meta.total_pages || 1}`;
        document.getElementById('btn-prev').disabled = (meta.page || 1) <= 1;
        document.getElementById('btn-next').disabled = (meta.page || 1) >= (meta.total_pages || 1);
    }

    document.getElementById('btn-refresh').addEventListener('click', () => {
        page = 1;
        loadRules();
    });
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (page > 1) {
            page -= 1;
            loadRules();
        }
    });
    document.getElementById('btn-next').addEventListener('click', () => {
        page += 1;
        loadRules();
    });

    loadRules();
</script>
@endsection
