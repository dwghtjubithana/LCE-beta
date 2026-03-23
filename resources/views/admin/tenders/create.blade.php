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
            <label for="tender-deadline">Inschrijfdeadline</label>
            <input class="input" id="tender-deadline" type="date">
        </div>
        <div class="form-field">
            <label for="tender-client">Opdrachtgever</label>
            <input class="input" id="tender-client" placeholder="Bijv. Ministerie OW">
        </div>
        <div class="form-field">
            <label for="tender-location">Locatie</label>
            <input class="input" id="tender-location" placeholder="Bijv. Paramaribo">
        </div>
        <div class="form-field">
            <label for="tender-sector">Sector</label>
            <input class="input" id="tender-sector" placeholder="Bijv. Olie & Gas">
        </div>
        <div class="form-field">
            <label for="tender-reference">Referentiecode</label>
            <input class="input" id="tender-reference" placeholder="Bijv. SC-2026-001">
        </div>
        <div class="form-field">
            <label for="tender-direct-work">Type opdracht</label>
            <select id="tender-direct-work">
                <option value="0">Standaard aanbesteding</option>
                <option value="1">Direct werk (micro-gig)</option>
            </select>
        </div>
        <div class="form-field">
            <label for="tender-contract-type">Contracttype</label>
            <input class="input" id="tender-contract-type" placeholder="Bijv. RFP of Openbare aanbesteding">
        </div>
        <div class="form-field">
            <label for="tender-budget">Budgetlabel</label>
            <input class="input" id="tender-budget" placeholder="Bijv. Middelgroot contract">
        </div>
        <div class="form-field">
            <label for="tender-url">Details-URL</label>
            <input class="input" id="tender-url" placeholder="https://...">
        </div>
        <div class="form-field">
            <label for="tender-source-name">Bronnaam</label>
            <input class="input" id="tender-source-name" placeholder="Bijv. Staatsolie Procurement">
        </div>
        <div class="form-field">
            <label for="tender-source-url">Bron-URL</label>
            <input class="input" id="tender-source-url" placeholder="https://...">
        </div>
        <div class="form-field">
            <label for="tender-cover-image">Cover image URL</label>
            <input class="input" id="tender-cover-image" placeholder="https://...">
        </div>
        <div class="form-field">
            <label for="tender-logo-image">Logo URL</label>
            <input class="input" id="tender-logo-image" placeholder="https://...">
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
        <div class="form-field">
            <label for="tender-eligibility">Geschiktheid</label>
            <textarea class="input" id="tender-eligibility" rows="4" placeholder="Welke documenten, ervaring of capaciteit zijn vereist?"></textarea>
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
        const deadline = document.getElementById('tender-deadline').value;
        const client = document.getElementById('tender-client').value.trim();
        const location = document.getElementById('tender-location').value.trim();
        const sector = document.getElementById('tender-sector').value.trim();
        const referenceCode = document.getElementById('tender-reference').value.trim();
        const contractType = document.getElementById('tender-contract-type').value.trim();
        const budgetLabel = document.getElementById('tender-budget').value.trim();
        const detailsUrl = document.getElementById('tender-url').value.trim();
        const sourceName = document.getElementById('tender-source-name').value.trim();
        const sourceUrl = document.getElementById('tender-source-url').value.trim();
        const coverImageUrl = document.getElementById('tender-cover-image').value.trim();
        const issuerLogoUrl = document.getElementById('tender-logo-image').value.trim();
        const description = document.getElementById('tender-description').value.trim();
        const eligibility = document.getElementById('tender-eligibility').value.trim();
        if (project) formData.append('project', project);
        if (date) formData.append('date', date);
        if (deadline) formData.append('submission_deadline', deadline);
        if (client) formData.append('client', client);
        if (location) formData.append('location', location);
        if (sector) formData.append('sector', sector);
        if (referenceCode) formData.append('reference_code', referenceCode);
        if (contractType) formData.append('contract_type', contractType);
        if (budgetLabel) formData.append('budget_label', budgetLabel);
        if (detailsUrl) formData.append('details_url', detailsUrl);
        if (sourceName) formData.append('source_name', sourceName);
        if (sourceUrl) formData.append('source_url', sourceUrl);
        if (coverImageUrl) formData.append('cover_image_url', coverImageUrl);
        if (issuerLogoUrl) formData.append('issuer_logo_url', issuerLogoUrl);
        if (description) formData.append('description', description);
        if (eligibility) formData.append('eligibility', eligibility);
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
