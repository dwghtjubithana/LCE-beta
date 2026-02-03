@extends('admin.layout')

@section('title', 'Tender Details')
@php($active = 'tenders')

@section('content')
<div class="page-header">
    <div>
        <h2>Tender Details</h2>
        <p>Review tender info and attachments.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/tenders'">Back to list</button>
    </div>
</div>

<div class="card">
    <div id="tender-detail" class="status">Loading...</div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const tenderId = {{ $id }};

    async function loadTender() {
        const res = await AdminApp.api(`/api/tenders/${tenderId}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('tender-detail').textContent = data.message || 'Failed to load tender.';
            return;
        }
        const tender = data.tender || data;
        document.getElementById('tender-detail').innerHTML = `
            <div><strong>${tender.title || tender.project || 'Tender'}</strong></div>
            <div class="status">Client: ${tender.client || '—'}</div>
            <div class="status">Date: ${tender.date || '—'}</div>
            <div class="status">URL: ${tender.details_url || '—'}</div>
            <div class="status">Description: ${tender.description || '—'}</div>
        `;
    }

    loadTender();
</script>
@endsection
