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
            <select class="input" id="rule-sector" multiple size="6"></select>
            <p class="status">Gebruik Ctrl/Cmd om meerdere sectoren te selecteren.</p>
        </div>
        <div class="form-field">
            <label for="rule-keywords">Required keywords</label>
            <textarea class="input" id="rule-keywords" rows="4" placeholder="1 keyword per regel (optional)"></textarea>
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
            <textarea class="input" id="rule-required-fields" rows="4" placeholder="1 veld per regel (optional)"></textarea>
        </div>
        <div class="form-field">
            <label for="rule-required-document">Required document?</label>
            <select class="input" id="rule-required-document">
                <option value="true" selected>Yes</option>
                <option value="false">No</option>
            </select>
        </div>
        <div class="form-field">
            <label for="rule-company-types">Company types</label>
            <select class="input" id="rule-company-types" multiple size="8"></select>
            <p class="status">Laat leeg om voor alle company types te gelden.</p>
        </div>
        <div class="form-field">
            <label for="rule-levels">Required levels</label>
            <select class="input" id="rule-levels" multiple size="4">
                <option value="FREE">FREE</option>
                <option value="BUSINESS">BUSINESS</option>
                <option value="ENTERPRISE">ENTERPRISE</option>
                <option value="PRO">PRO</option>
            </select>
            <p class="status">Laat leeg om geen level-gating af te dwingen.</p>
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

    function selectedValues(id) {
        const el = document.getElementById(id);
        if (!el) return [];
        return Array.from(el.selectedOptions || []).map((o) => String(o.value || '').trim()).filter(Boolean);
    }

    function splitLines(value) {
        return String(value || '')
            .split('\n')
            .map((s) => s.trim())
            .filter(Boolean);
    }

    async function loadRuleMeta() {
        const res = await AdminApp.api('/api/admin/compliance-rules/meta');
        const data = await AdminApp.readJson(res);
        if (!res.ok) return;
        const meta = data.meta || {};
        const sectors = Array.isArray(meta.sectors) ? meta.sectors : [];
        const companyTypes = Array.isArray(meta.company_types) ? meta.company_types : [];
        const levels = Array.isArray(meta.levels) ? meta.levels : [];

        const sectorEl = document.getElementById('rule-sector');
        sectorEl.innerHTML = sectors.map((s) => `<option value="${s}">${s}</option>`).join('');

        const companyTypeEl = document.getElementById('rule-company-types');
        companyTypeEl.innerHTML = companyTypes.map((c) => (
            `<option value="${c.key}">${c.label || c.key}</option>`
        )).join('');

        const levelEl = document.getElementById('rule-levels');
        levelEl.innerHTML = levels.map((level) => `<option value="${level}">${level}</option>`).join('');
    }

    document.getElementById('btn-create').addEventListener('click', async () => {
        const statusEl = document.getElementById('create-status');
        const expiryRequiredRaw = document.getElementById('rule-expiry-required').value;
        const requiredFields = splitLines(document.getElementById('rule-required-fields').value);
        const requiredDocument = document.getElementById('rule-required-document').value === 'true';
        const companyTypeKeys = selectedValues('rule-company-types');
        const requiredLevels = selectedValues('rule-levels').map((s) => s.toUpperCase());
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
            sector_applicability: selectedValues('rule-sector'),
            required_keywords: splitLines(document.getElementById('rule-keywords').value),
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

    loadRuleMeta();
</script>
@endsection
