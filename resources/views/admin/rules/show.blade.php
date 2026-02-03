@extends('admin.layout')

@section('title', 'Rule Details')
@php($active = 'rules')

@section('content')
<div class="page-header">
    <div>
        <h2>Rule Details</h2>
        <p>Review requirements and constraints.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/compliance-rules'">Back to list</button>
    </div>
</div>

<div class="card">
    <div id="rule-detail" class="status">Loading...</div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const ruleId = {{ $id }};

    async function loadRule() {
        const res = await AdminApp.api(`/api/admin/compliance-rules/${ruleId}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('rule-detail').textContent = data.message || 'Failed to load rule.';
            return;
        }
        const rule = data.rule;
        const keywords = (rule.required_keywords || []).join(', ') || '—';
        const sectors = (rule.sector_applicability || []).join(', ') || '—';
        const constraints = rule.constraints || {};
        const expiry = typeof constraints.expiry_required === 'boolean'
            ? (constraints.expiry_required ? 'Yes' : 'No')
            : '—';
        const requiredFields = Array.isArray(constraints.required_fields)
            ? constraints.required_fields.join(', ')
            : '—';
        document.getElementById('rule-detail').innerHTML = `
            <div class="card" style="margin-bottom:16px;">
                <div class="status" style="font-weight:700; font-size:16px;">${rule.document_type || '—'}</div>
                <div class="status">Max age: ${rule.max_age_months || '—'} months</div>
                <div class="status">Keywords: ${keywords}</div>
                <div class="status">Sector applicability: ${sectors}</div>
            </div>
            <div class="card">
                <div class="status" style="font-weight:600;">Constraints</div>
                <div class="status">Expiry required: ${expiry}</div>
                <div class="status">Required fields: ${requiredFields}</div>
            </div>
        `;
    }

    loadRule();
</script>
@endsection
