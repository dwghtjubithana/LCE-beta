@extends('admin.layout')

@section('title', 'Create Company')
@php($active = 'companies')

@section('content')
<div class="page-header">
    <div>
        <h2>Create Company</h2>
        <p>Register a new company profile for a user.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/companies'">Back to list</button>
    </div>
</div>

    <div class="card">
    <div class="form-stack">
        <div class="form-field">
            <label for="company-owner">Owner User ID</label>
            <input class="input" id="company-owner" placeholder="e.g. 4">
        </div>
        <div class="form-field">
            <label for="company-name">Company name</label>
            <input class="input" id="company-name" placeholder="e.g. Suriname Logistics">
        </div>
        <div class="form-field">
            <label for="company-sector">Sector</label>
            <input class="input" id="company-sector" placeholder="e.g. Transport">
        </div>
        <div class="form-field">
            <label for="company-experience">Experience</label>
            <input class="input" id="company-experience" placeholder="Optional">
        </div>
        <div class="form-field">
            <label for="company-email">Contact email</label>
            <input class="input" id="company-email" placeholder="Optional">
        </div>
        <div class="form-field">
            <label for="company-phone">Contact phone</label>
            <input class="input" id="company-phone" placeholder="Optional">
        </div>
        <div class="form-field">
            <label for="company-address">Address</label>
            <input class="input" id="company-address" placeholder="Optional">
        </div>
        <div class="form-field">
            <label for="company-city">City</label>
            <input class="input" id="company-city" placeholder="Optional">
        </div>
        <div class="form-field">
            <label for="company-country">Country</label>
            <input class="input" id="company-country" placeholder="Optional">
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-create">Create company</button>
    </div>
    <div class="status" id="create-status"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    document.getElementById('btn-create').addEventListener('click', async () => {
        const statusEl = document.getElementById('create-status');
        const ownerUserIdRaw = document.getElementById('company-owner').value.trim();
        const companyName = document.getElementById('company-name').value.trim();
        const sector = document.getElementById('company-sector').value.trim();
        if (!ownerUserIdRaw || Number.isNaN(Number(ownerUserIdRaw))) {
            AdminApp.setStatus(statusEl, 'Owner user ID is required.', 'error');
            return;
        }
        if (!companyName) {
            AdminApp.setStatus(statusEl, 'Company name is required.', 'error');
            return;
        }
        if (!sector) {
            AdminApp.setStatus(statusEl, 'Sector is required.', 'error');
            return;
        }
        const contact = {
            email: document.getElementById('company-email').value.trim() || null,
            phone: document.getElementById('company-phone').value.trim() || null,
            address: document.getElementById('company-address').value.trim() || null,
            city: document.getElementById('company-city').value.trim() || null,
            country: document.getElementById('company-country').value.trim() || null,
        };
        const hasContact = Object.values(contact).some(value => value);
        const payload = {
            owner_user_id: Number(ownerUserIdRaw),
            company_name: companyName,
            sector,
            experience: document.getElementById('company-experience').value.trim() || null,
            contact: hasContact ? contact : null
        };
        const res = await AdminApp.api('/api/admin/companies', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'Company created successfully.', 'success');
        window.location.href = `/admin/companies/${data.company.id}`;
    });
</script>
@endsection
