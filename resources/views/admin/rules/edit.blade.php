@extends('admin.layout')

@section('title', 'Edit Compliance Rule')
@php($active = 'rules')

@section('content')
<div class="page-header">
    <div>
        <h2>Edit Compliance Rule</h2>
        <p>Update required keywords and constraints.</p>
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
        <input type="hidden" id="rule-constraints-raw">
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-save">Save changes</button>
    </div>
    <div class="status" id="edit-status"></div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const ruleId = {{ $id }};

    async function loadRule() {
        const statusEl = document.getElementById('edit-status');
        const res = await AdminApp.api(`/api/admin/compliance-rules/${ruleId}`);
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        const rule = data.rule || {};
        document.getElementById('rule-type').value = rule.document_type || '';
        document.getElementById('rule-sector').value = (rule.sector_applicability || []).join(', ');
        document.getElementById('rule-keywords').value = (rule.required_keywords || []).join(', ');
        document.getElementById('rule-max-age').value = rule.max_age_months ?? '';
        const constraints = rule.constraints || {};
        document.getElementById('rule-expiry-required').value =
            typeof constraints.expiry_required === 'boolean' ? String(constraints.expiry_required) : '';
        document.getElementById('rule-required-fields').value = Array.isArray(constraints.required_fields)
            ? constraints.required_fields.join(', ')
            : '';
        document.getElementById('rule-constraints-raw').value = rule.constraints ? JSON.stringify(rule.constraints) : '';
    }

    document.getElementById('btn-save').addEventListener('click', async () => {
        const statusEl = document.getElementById('edit-status');
        const expiryRequiredRaw = document.getElementById('rule-expiry-required').value;
        const requiredFields = document.getElementById('rule-required-fields').value
            .split(',')
            .map(s => s.trim())
            .filter(Boolean);
        let constraints = null;
        if (expiryRequiredRaw !== '' || requiredFields.length) {
            constraints = {
                expiry_required: expiryRequiredRaw === '' ? null : expiryRequiredRaw === 'true',
                required_fields: requiredFields.length ? requiredFields : null
            };
        }
        const raw = document.getElementById('rule-constraints-raw').value.trim();
        if (raw) {
            try {
                const rawObj = JSON.parse(raw);
                if (rawObj && typeof rawObj === 'object') {
                    constraints = Object.assign({}, rawObj, constraints || {});
                }
            } catch {
                // ignore invalid hidden raw
            }
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
        const res = await AdminApp.api(`/api/admin/compliance-rules/${ruleId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'Rule updated successfully.', 'success');
        window.location.href = `/admin/compliance-rules/${data.rule.id}`;
    });

    loadRule();
</script>
@endsection
