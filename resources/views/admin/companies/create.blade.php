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
            <select id="company-owner"></select>
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
            <label for="company-type-key">Company type (optional)</label>
            <select id="company-type-key">
                <option value="">Geen type geselecteerd</option>
            </select>
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

    async function loadOwnerUsers() {
        const ownerEl = document.getElementById('company-owner');
        ownerEl.innerHTML = '<option value="">Select owner user</option>';
        const res = await AdminApp.api('/api/admin/users?limit=100&page=1');
        const data = await AdminApp.readJson(res);
        if (!res.ok) return;
        const users = Array.isArray(data.users) ? data.users : [];
        users.forEach((u) => {
            const opt = document.createElement('option');
            opt.value = String(u.id);
            const contact = u.email || u.phone || 'no-contact';
            opt.textContent = `${u.id} - ${u.username || 'user'} (${contact})`;
            ownerEl.appendChild(opt);
        });
    }

    async function loadCompanyTypes() {
        const typeEl = document.getElementById('company-type-key');
        const res = await AdminApp.api('/api/admin/compliance-rules/meta');
        const data = await AdminApp.readJson(res);
        if (!res.ok) return;
        const companyTypes = Array.isArray(data?.meta?.company_types) ? data.meta.company_types : [];
        companyTypes.forEach((type) => {
            const opt = document.createElement('option');
            opt.value = type.key || '';
            opt.textContent = type.label || type.key || '';
            typeEl.appendChild(opt);
        });
    }

    document.getElementById('btn-create').addEventListener('click', async () => {
        const statusEl = document.getElementById('create-status');
        const ownerUserIdRaw = document.getElementById('company-owner').value;
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
            company_type_key: document.getElementById('company-type-key').value || null,
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

    loadOwnerUsers();
    loadCompanyTypes();
</script>
@endsection
