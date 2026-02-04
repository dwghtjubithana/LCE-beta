@extends('admin.layout')

@section('title', 'Aanbesteding aanmaken')
@php($active = 'tenders')

@section('content')
<div class="page-header">
    <div>
        <h2>Aanbesteding aanmaken</h2>
        <p>Voeg een nieuwe aanbesteding toe.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/tenders'">Terug naar overzicht</button>
    </div>
</div>

<div class="card">
    <div class="form-stack">
        <div class="form-field">
            <label for="tender-title">Titel</label>
            <input class="input" id="tender-title" placeholder="Bijv. Levering zand">
        </div>
        <div class="form-field">
            <label for="tender-project">Project</label>
            <input class="input" id="tender-project" placeholder="Optioneel">
        </div>
        <div class="form-field">
            <label for="tender-date">Datum</label>
            <input class="input" id="tender-date" type="date">
        </div>
        <div class="form-field">
            <label for="tender-client">Opdrachtgever</label>
            <input class="input" id="tender-client" placeholder="Bijv. Ministerie OW">
        </div>
        <div class="form-field">
            <label for="tender-direct-work">Type opdracht</label>
            <select id="tender-direct-work">
                <option value="0">Standaard aanbesteding</option>
                <option value="1">Direct werk (micro-gig)</option>
            </select>
        </div>
        <div class="form-field">
            <label for="tender-url">Details-URL</label>
            <input class="input" id="tender-url" placeholder="https://...">
        </div>
        <div class="form-field">
            <label for="tender-attachments">Bijlagen</label>
            <textarea class="input" id="tender-attachments" rows="3" placeholder="1 URL per regel (optioneel)"></textarea>
        </div>
        <div class="form-field">
            <label for="tender-attachment-files">Document/foto bijlage (optioneel)</label>
            <input class="input" id="tender-attachment-files" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        </div>
        <div class="form-field">
            <label for="tender-description">Omschrijving</label>
            <input class="input" id="tender-description" placeholder="Korte samenvatting (optioneel)">
        </div>
    </div>
    <div class="actions" style="margin-top:12px;">
        <button class="btn" id="btn-create">Aanbesteding aanmaken</button>
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
        const attachments = attachmentsRaw
            ? attachmentsRaw.split('\n').map(s => s.trim()).filter(Boolean)
            : null;
        const title = document.getElementById('tender-title').value.trim();
        if (!title) {
            AdminApp.setStatus(statusEl, 'Titel is verplicht.', 'error');
            return;
        }
        const formData = new FormData();
        formData.append('title', title);
        const project = document.getElementById('tender-project').value.trim();
        const date = document.getElementById('tender-date').value;
        const client = document.getElementById('tender-client').value.trim();
        const detailsUrl = document.getElementById('tender-url').value.trim();
        const description = document.getElementById('tender-description').value.trim();
        if (project) formData.append('project', project);
        if (date) formData.append('date', date);
        if (client) formData.append('client', client);
        if (detailsUrl) formData.append('details_url', detailsUrl);
        if (description) formData.append('description', description);
        formData.append('is_direct_work', document.getElementById('tender-direct-work').value === '1' ? '1' : '0');
        if (attachments && attachments.length) {
            formData.append('attachments_urls', attachments.join('\n'));
        }
        const files = document.getElementById('tender-attachment-files').files || [];
        Array.from(files).forEach((file) => formData.append('attachments_files[]', file));

        const res = await AdminApp.api('/api/admin/tenders', {
            method: 'POST',
            body: formData
        });
        const data = await AdminApp.readJson(res);
        if (!res.ok) {
            AdminApp.setStatus(statusEl, AdminApp.formatError(data), 'error');
            return;
        }
        AdminApp.setStatus(statusEl, 'Aanbesteding succesvol aangemaakt.', 'success');
        window.location.href = `/admin/tenders/${data.tender.id}`;
    });
</script>
@endsection
