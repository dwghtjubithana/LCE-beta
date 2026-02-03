@extends('admin.layout')

@section('title', 'Document Details')
@php($active = 'documents')

@section('content')
<div class="page-header">
    <div>
        <h2>Document Details</h2>
        <p>Review processing status and extracted metadata.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/documents'">Back to list</button>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3 style="margin-top:0;">Document</h3>
        <div id="doc-summary" class="status">Loading...</div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Status</h3>
        <div id="doc-status" class="status">Loading...</div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Extracted Data</h3>
    <div id="doc-data" class="status">Loading...</div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const docId = {{ $id }};

    async function loadDocument() {
        const res = await AdminApp.api(`/api/admin/documents/${docId}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('doc-summary').textContent = data.message || 'Failed to load document.';
            return;
        }
        const doc = data.document;
        document.getElementById('doc-summary').innerHTML = `
            <div><strong>${doc.original_filename || '—'}</strong></div>
            <div class="status">Category: ${doc.category_selected || '—'}</div>
            <div class="status">Detected: ${doc.detected_type || '—'}</div>
            <div class="status">Company ID: ${doc.company_id || '—'}</div>
        `;
        document.getElementById('doc-status').innerHTML = `
            <div class="status">Status: ${doc.status || '—'}</div>
            <div class="status">Expiry: ${doc.expiry_date || '—'}</div>
        `;
        document.getElementById('doc-data').textContent = JSON.stringify(doc.extracted_data || {}, null, 2);
    }

    loadDocument();
</script>
@endsection
