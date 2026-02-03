@extends('admin.layout')

@section('title', 'Create Tender')
@php($active = 'tenders')

@section('content')
<div class="page-header">
    <div>
        <h2>Create Tender</h2>
        <p>Add a new tender listing for the marketplace.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/tenders'">Back to list</button>
    </div>
</div>

<div class="card">
    <div class="filters">
        <input class="input" id="tender-title" placeholder="Title">
        <input class="input" id="tender-project" placeholder="Project (optional)">
        <input class="input" id="tender-date" type="date">
        <input class="input" id="tender-client" placeholder="Client">
        <input class="input" id="tender-url" placeholder="Details URL">
        <input class="input" id="tender-attachments" placeholder='Attachments JSON (optional)'>
        <input class="input" id="tender-description" placeholder="Description">
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-create">Create tender</button>
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
        const attachmentsRaw = document.getElementById('tender-attachments').value.trim();
        let attachments = null;
        if (attachmentsRaw) {
            try {
                attachments = JSON.parse(attachmentsRaw);
            } catch {
                AdminApp.setStatus(statusEl, 'Attachments must be valid JSON.', 'error');
                return;
            }
        }
        const payload = {
            title: document.getElementById('tender-title').value.trim(),
            project: document.getElementById('tender-project').value.trim() || null,
            date: document.getElementById('tender-date').value,
            client: document.getElementById('tender-client').value.trim() || null,
            details_url: document.getElementById('tender-url').value.trim() || null,
            attachments: attachments,
            description: document.getElementById('tender-description').value.trim() || null,
        };
        if (!payload.title || !payload.client) {
            AdminApp.setStatus(statusEl, 'Title and client are required.', 'error');
            return;
        }
        const res = await AdminApp.api('/api/admin/tenders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'Tender created successfully.', 'success');
        window.location.href = `/admin/tenders/${data.tender.id}`;
    });
</script>
@endsection
