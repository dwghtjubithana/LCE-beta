@extends('admin.layout')

@section('title', 'Create Compliance Rule')
@php($active = 'rules')

@section('content')
<div class="page-header">
    <div>
        <h2>Create Compliance Rule</h2>
        <p>Define required keywords and constraints.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/compliance-rules'">Back to list</button>
    </div>
</div>

<div class="card">
    <div class="form-stack">
        <div class="form-field">
            <label for="rule-type">Document type</label>
            <input class="input" id="rule-type" placeholder="e.g. Belastingverklaring">
        </div>
        <div class="form-field">
            <label for="rule-sector">Sector applicability</label>
            <input class="input" id="rule-sector" placeholder="Comma separated (optional)">
        </div>
        <div class="form-field">
            <label for="rule-keywords">Required keywords</label>
            <input class="input" id="rule-keywords" placeholder="Comma separated (optional)">
        </div>
        <div class="form-field">
            <label for="rule-max-age">Max age (months)</label>
            <input class="input" id="rule-max-age" type="number" placeholder="e.g. 12">
        </div>
        <div class="form-field">
            <label for="rule-expiry-required">Expiry required?</label>
            <select class="input" id="rule-expiry-required">
                <option value="">Select</option>
                <option value="true">Yes</option>
                <option value="false">No</option>
            </select>
        </div>
        <div class="form-field">
            <label for="rule-required-fields">Required fields</label>
            <input class="input" id="rule-required-fields" placeholder="Comma separated (optional)">
        </div>
        <div class="form-field">
            <label for="rule-required-document">Required document?</label>
            <select class="input" id="rule-required-document">
                <option value="true" selected>Yes</option>
                <option value="false">No</option>
            </select>
        </div>
        <div class="form-field">
            <label for="rule-company-types">Company type keys</label>
            <input class="input" id="rule-company-types" placeholder="Comma separated (optional)">
        </div>
        <div class="form-field">
            <label for="rule-levels">Required levels</label>
            <input class="input" id="rule-levels" placeholder="Comma separated (FREE, BUSINESS, ENTERPRISE)">
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-create">Create rule</button>
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
        const expiryRequiredRaw = document.getElementById('rule-expiry-required').value;
        const requiredFields = document.getElementById('rule-required-fields').value
            .split(',')
            .map(s => s.trim())
            .filter(Boolean);
        const requiredDocument = document.getElementById('rule-required-document').value === 'true';
        const companyTypeKeys = document.getElementById('rule-company-types').value
            .split(',')
            .map(s => s.trim())
            .filter(Boolean);
        const requiredLevels = document.getElementById('rule-levels').value
            .split(',')
            .map(s => s.trim().toUpperCase())
            .filter(Boolean);
        let constraints = null;
        if (expiryRequiredRaw !== '' || requiredFields.length || !requiredDocument || companyTypeKeys.length || requiredLevels.length) {
            constraints = {
                expiry_required: expiryRequiredRaw === '' ? null : expiryRequiredRaw === 'true',
                required_fields: requiredFields.length ? requiredFields : null,
                required_document: requiredDocument,
                company_type_keys: companyTypeKeys.length ? companyTypeKeys : null,
                required_levels: requiredLevels.length ? requiredLevels : null
            };
        }
        const payload = {
            document_type: document.getElementById('rule-type').value.trim(),
            sector_applicability: document.getElementById('rule-sector').value.split(',').map(s => s.trim()).filter(Boolean),
            required_keywords: document.getElementById('rule-keywords').value.split(',').map(s => s.trim()).filter(Boolean),
            max_age_months: Number(document.getElementById('rule-max-age').value) || null,
            constraints: constraints
        };
        if (!payload.document_type) {
            AdminApp.setStatus(statusEl, 'Document type is required.', 'error');
            return;
        }
        const res = await AdminApp.api('/api/admin/compliance-rules', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'Rule created successfully.', 'success');
        window.location.href = `/admin/compliance-rules/${data.rule.id}`;
    });
</script>
@endsection
