@extends('admin.layout')

@section('title', 'Aanbesteding details')
@php($active = 'tenders')

@section('content')
<div class="page-header">
    <div>
        <h2>Aanbesteding details</h2>
        <p>Bekijk aanbestedingsinformatie, status en bijlagen.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/tenders'">Terug naar overzicht</button>
    </div>
</div>

<div class="card">
    <div id="tender-detail" class="status">Laden...</div>
</div>
@endsection

@section('scripts')
<script>
    AdminApp.requireAuth();
    AdminApp.initTopbar();

    const tenderId = {{ $id }};

    async function loadTender() {
        const res = await AdminApp.api(`/api/admin/tenders/${tenderId}`);
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('tender-detail').textContent = data.message || 'Aanbesteding laden mislukt.';
            return;
        }
        const tender = data.tender || data;
        const status = (tender.status || 'APPROVED').toUpperCase();
        const statusLabel = status === 'PENDING' ? 'In afwachting' : (status === 'REJECTED' ? 'Afgewezen' : 'Goedgekeurd');
        const statusClass = status === 'PENDING' ? 'badge-warn' : (status === 'REJECTED' ? 'badge-danger' : 'badge-success');
        const reviewActions = status === 'PENDING'
            ? `<div class="actions" style="margin-top:12px;">
                    <button class="btn secondary" id="approveTenderBtn">Goedkeuren</button>
                    <button class="btn danger" id="rejectTenderBtn">Afwijzen</button>
               </div>`
            : '';
        const attachments = Array.isArray(tender.attachments) ? tender.attachments : [];
        const attachmentRows = attachments.length
            ? attachments.map((item, idx) => {
                if (typeof item === 'string') {
                    return `<div class="status"><a href="${item}" target="_blank" rel="noopener">Externe link ${idx + 1}</a></div>`;
                }
                if (item.type === 'url' && item.url) {
                    return `<div class="status"><a href="${item.url}" target="_blank" rel="noopener">Externe link ${idx + 1}</a></div>`;
                }
                const fileName = item.name || `Bestand ${idx + 1}`;
                return `<div class="status"><a href="#" data-open-attachment="${idx}">${fileName}</a></div>`;
              }).join('')
            : '<div class="status">Geen bijlagen</div>';
        document.getElementById('tender-detail').innerHTML = `
            <div><strong>${tender.title || tender.project || 'Aanbesteding'}</strong></div>
            <div class="status">Opdrachtgever: ${tender.client || '—'}</div>
            <div class="status">Datum: ${AdminApp.formatDate(tender.date)}</div>
            <div class="status">Details URL: ${tender.details_url || '—'}</div>
            <div class="status">Omschrijving: ${tender.description || '—'}</div>
            <div class="status">Direct werk: ${tender.is_direct_work ? 'Ja' : 'Nee'}</div>
            <div class="status">Status: <span class="badge ${statusClass}">${statusLabel}</span></div>
            <div style="margin-top:10px;">
              <strong>Bijlagen</strong>
              ${attachmentRows}
            </div>
            ${reviewActions}
        `;

        document.getElementById('approveTenderBtn')?.addEventListener('click', () => updateStatus('approve'));
        document.getElementById('rejectTenderBtn')?.addEventListener('click', () => updateStatus('reject'));
        document.querySelectorAll('[data-open-attachment]').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const idx = el.getAttribute('data-open-attachment');
                openAttachment(idx);
            });
        });
    }

    async function openAttachment(index) {
        const res = await AdminApp.api(`/api/admin/tenders/${tenderId}/attachments/${index}`);
        const contentType = res.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await res.json();
            const url = data?.attachment?.url;
            if (url) {
                window.open(url, '_blank');
                return;
            }
            alert(data.message || 'Bijlage niet gevonden.');
            return;
        }
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            alert(data.message || 'Bijlage openen mislukt.');
            return;
        }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');
    }

    async function updateStatus(action) {
        const res = await AdminApp.api(`/api/admin/tenders/${tenderId}/${action}`, { method: 'POST' });
        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Status bijwerken mislukt.');
            return;
        }
        loadTender();
    }

    loadTender();
</script>
@endsection
