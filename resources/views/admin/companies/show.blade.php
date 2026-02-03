@extends('admin.layout')

@section('title', 'Company Details')
@php($active = 'companies')

@section('content')
<div class="page-header">
    <div>
        <h2>Company Details</h2>
        <p>Profile, verification status, and document progress.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/companies'">Back to list</button>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3 style="margin-top:0;">Profile</h3>
        <div id="company-profile" class="status">Loading...</div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Score</h3>
        <div id="company-score" class="status">Loading...</div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Required Documents</h3>
    <div id="company-docs" class="status">Loading...</div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const companyId = {{ $id }};

    async function loadCompany() {
        const res = await AdminApp.api(`/api/admin/companies/${companyId}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('company-profile').textContent = data.message || 'Failed to load company.';
            return;
        }
        const company = data.company;
        document.getElementById('company-profile').innerHTML = `
            <div><strong>${company.company_name || '—'}</strong></div>
            <div class="status">Sector: ${company.sector || '—'}</div>
            <div class="status">Owner user ID: ${company.owner_user_id || '—'}</div>
        `;
        document.getElementById('company-score').innerHTML = `
            <div class="status">Current score: ${company.current_score ?? 0}%</div>
            <div class="status">Verification: ${company.verification_level || 'unverified'}</div>
        `;
    }

    async function loadDashboard() {
        const res = await AdminApp.api(`/api/companies/${companyId}/dashboard`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('company-docs').textContent = data.message || 'Failed to load required documents.';
            return;
        }
        const docs = data.required_documents || [];
        if (!docs.length) {
            document.getElementById('company-docs').innerHTML = '<div class="status">No required document data.</div>';
            return;
        }
        const body = docs.map(doc => `
            <tr>
                <td>${doc.type || '-'}</td>
                <td>${doc.status || '-'}</td>
            </tr>
        `).join('');
        document.getElementById('company-docs').innerHTML = `
            <table class="table">
                <thead><tr><th>Document</th><th>Status</th></tr></thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }

    loadCompany();
    loadDashboard();
</script>
@endsection
