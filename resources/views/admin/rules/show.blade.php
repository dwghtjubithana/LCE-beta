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
        document.getElementById('rule-detail').innerHTML = `
            <div><strong>${rule.document_type || '—'}</strong></div>
            <div class="status">Max age: ${rule.max_age_months || '—'} months</div>
            <div class="status">Keywords: ${(rule.required_keywords || []).join(', ') || '—'}</div>
            <div class="status">Constraints: ${JSON.stringify(rule.constraints || {}, null, 2)}</div>
        `;
    }

    loadRule();
</script>
@endsection
