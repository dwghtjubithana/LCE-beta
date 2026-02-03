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
    <div class="filters">
        <input class="input" id="rule-type" placeholder="Document type">
        <input class="input" id="rule-sector" placeholder="Sector applicability (comma separated)">
        <input class="input" id="rule-keywords" placeholder="Required keywords (comma separated)">
        <input class="input" id="rule-max-age" type="number" placeholder="Max age months">
        <input class="input" id="rule-constraints" placeholder='Constraints JSON (optional)'>
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
        const constraintsRaw = document.getElementById('rule-constraints').value.trim();
        let constraints = null;
        if (constraintsRaw) {
            try {
                constraints = JSON.parse(constraintsRaw);
            } catch {
                AdminApp.setStatus(statusEl, 'Constraints must be valid JSON.', 'error');
                return;
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
