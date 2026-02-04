@extends('admin.layout')

@section('title', 'Documentdetails')
@php($active = 'documents')

@section('content')
<div class="page-header">
    <div>
        <h2>Documentdetails</h2>
        <p>Beoordeel status, metadata en inhoud.</p>
    </div>
    <div class="actions">
        <button class="btn secondary" onclick="window.location.href='/admin/documents'">Terug naar overzicht</button>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3 style="margin-top:0;">Document</h3>
        <div id="doc-summary" class="status">Laden...</div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Status</h3>
        <div id="doc-status" class="status">Laden...</div>
        <div class="form-stack" style="margin-top:16px;">
            <div class="form-field">
                <label for="admin-note">Opmerking voor het dossier</label>
                <textarea id="admin-note" class="input" rows="3" placeholder="Waarom keur je dit goed of af?"></textarea>
            </div>
            <div class="actions">
                <button class="btn secondary" id="approveDocBtn">Goedkeuren</button>
                <button class="btn danger" id="rejectDocBtn">Afwijzen</button>
            </div>
            <div class="status" id="doc-action-status"></div>
        </div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Geanalyseerde gegevens</h3>
    <div id="doc-data" class="status">Laden...</div>
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
            document.getElementById('doc-summary').textContent = data.message || 'Document laden mislukt.';
            return;
        }
        const settingsRes = await AdminApp.api('/api/admin/ai-settings');
        const settingsData = await settingsRes.json().catch(() => ({}));
        const debugEnabled = Boolean(settingsData?.settings?.gemini_debug_full);
        const doc = data.document;
        const expiryLabel = doc.expiry_date ? AdminApp.formatDate(doc.expiry_date) : '—';
        const extracted = doc.extracted_data || {};
        if (!debugEnabled) {
            delete extracted.ai_debug_full;
            delete extracted.ai_debug_meta;
        }
        document.getElementById('doc-summary').innerHTML = `
            <div><strong>${doc.original_filename || '—'}</strong></div>
            <div class="status">Categorie: ${doc.category_selected || '—'}</div>
            <div class="status">Gedetecteerd: ${doc.detected_type || '—'}</div>
            <div class="status">Bedrijf ID: ${doc.company_id || '—'}</div>
            <div class="status">ID subtype: ${(doc.extracted_data && doc.extracted_data.id_subtype) ? doc.extracted_data.id_subtype : '—'}</div>
            <div class="actions" style="margin-top:10px;">
                <button class="btn secondary" id="openFrontDocBtn">Bekijk voorzijde</button>
                <button class="btn secondary" id="openBackDocBtn">Bekijk achterzijde</button>
            </div>
        `;
        const statusLabel = mapStatus(doc.status);
        document.getElementById('doc-status').innerHTML = `
            <div class="status">Status: <span class="badge ${statusLabel.className}">${statusLabel.label}</span></div>
            <div class="status">Vervaldatum: ${expiryLabel}</div>
            <div class="status">AI Advies: ${doc.ai_feedback || '—'}</div>
        `;

        document.getElementById('doc-data').innerHTML = renderExtractedData(extracted, debugEnabled);

        document.getElementById('approveDocBtn').onclick = () => updateDocStatus('approve');
        document.getElementById('rejectDocBtn').onclick = () => updateDocStatus('reject');
        document.getElementById('openFrontDocBtn').onclick = () => openDocFile('FRONT');
        document.getElementById('openBackDocBtn').onclick = () => openDocFile('BACK');
    }

    function mapStatus(status) {
        const value = (status || '').toUpperCase();
        if (value === 'VALID') return { label: 'Goedgekeurd', className: 'badge-success' };
        if (value === 'INVALID') return { label: 'Afgewezen', className: 'badge-danger' };
        if (value === 'MANUAL_REVIEW') return { label: 'Handmatige controle', className: 'badge-warn' };
        if (value === 'PROCESSING') return { label: 'In verwerking', className: 'badge-warn' };
        return { label: status || 'Onbekend', className: 'badge' };
    }

    function renderList(title, items) {
        if (!items || !items.length) return '';
        const list = items.map(item => `<li>${escapeHtml(String(item))}</li>`).join('');
        return `<div class="card" style="margin-top:12px;"><h4 style="margin-top:0;">${title}</h4><ul>${list}</ul></div>`;
    }

    function renderExtractedData(extracted, debugEnabled) {
        const ocrText = extracted.ocr_text || extracted?.ocr?.text || null;
        const ocrConf = extracted.ocr_confidence ?? extracted?.ocr?.confidence;
        const aiSummary = extracted.ai_summary || null;

        const sections = [];
        sections.push(`<div class="status"><strong>OCR-kwaliteit:</strong> ${ocrConf ?? '—'}</div>`);

        if (ocrText) {
            sections.push(`
                <details style="margin-top:12px;">
                    <summary>OCR-tekst bekijken</summary>
                    <pre style="white-space:pre-wrap; font-size:12px; margin-top:8px;">${escapeHtml(ocrText)}</pre>
                </details>
            `);
        }

        const uploadedFiles = Array.isArray(extracted.uploaded_files) ? extracted.uploaded_files : [];
        if (uploadedFiles.length) {
            const rows = uploadedFiles.map((item) => `
                <div class="status"><strong>${escapeHtml(item.side || 'BESTAND')}:</strong> ${escapeHtml(item.filename || item.path || '—')}</div>
            `).join('');
            sections.push(`<div class="card" style="margin-top:12px;">
                <h4 style="margin-top:0;">Bestandszijden</h4>
                ${rows}
            </div>`);
        }

        if (aiSummary) {
            sections.push(`<div class="card" style="margin-top:12px;">
                <h4 style="margin-top:0;">AI Samenvatting</h4>
                <div class="status">${escapeHtml(aiSummary.summary || '—')}</div>
                ${renderList('Bevindingen', aiSummary.findings)}
                ${renderList('Ontbreekt', aiSummary.missing_items)}
                ${renderList('Verbeteringen', aiSummary.improvements)}
            </div>`);
        }

        if (debugEnabled && (extracted.ai_debug_full || extracted.ai_debug_meta)) {
            sections.push(`<details style="margin-top:12px;">
                <summary>Debug-data</summary>
                <pre style="white-space:pre-wrap; font-size:12px; margin-top:8px;">${escapeHtml(JSON.stringify({
                    ai_debug_meta: extracted.ai_debug_meta || null,
                    ai_debug_full: extracted.ai_debug_full || null,
                }, null, 2))}</pre>
            </details>`);
        }

        if (!sections.length) {
            return '<div class="status">Geen gegevens beschikbaar.</div>';
        }
        return sections.join('');
    }

    async function updateDocStatus(action) {
        const statusEl = document.getElementById('doc-action-status');
        const note = document.getElementById('admin-note')?.value?.trim() || '';
        statusEl.textContent = 'Bezig met opslaan...';
        const res = await AdminApp.api(`/api/admin/documents/${docId}/${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ note })
        });
        const data = await res.json();
        if (!res.ok) {
            statusEl.textContent = data.message || 'Opslaan mislukt.';
            statusEl.classList.add('error');
            return;
        }
        statusEl.textContent = 'Status bijgewerkt.';
        statusEl.classList.remove('error');
        loadDocument();
    }

    async function openDocFile(side) {
        const res = await AdminApp.api(`/api/admin/documents/${docId}/file/${side}`);
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            alert(data.message || 'Bestand openen mislukt.');
            return;
        }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    loadDocument();
</script>
@endsection
