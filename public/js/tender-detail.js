const API_BASE = '/api';
const token = sessionStorage.getItem('lce_token') || localStorage.getItem('lce_token');

document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  const page = document.querySelector('[data-tender-id]');
  const id = page?.getAttribute('data-tender-id');
  if (!id) return;
  fetchTender(id);
});

async function fetchTender(id) {
  const endpoint = token ? `${API_BASE}/tenders/${id}` : `${API_BASE}/public/tenders/${id}`;
  try {
    const res = await fetch(endpoint, {
      headers: token ? { 'Authorization': `Bearer ${token}` } : {},
    });
    if (!res.ok) throw new Error('Tender niet gevonden');
    const data = await res.json();
    const tender = data?.tender;
    if (!tender) throw new Error('Tender niet gevonden');
    renderTender(tender);
  } catch (err) {
    setText('detailTitle', 'Tender niet gevonden');
    setText('detailBody', 'Deze tender bestaat niet of is verwijderd.');
  }
}

function renderTender(tender) {
  const sourceUrl = safeExternalUrl(tender.details_url || tender.source_url);

  const hero = document.querySelector('.hero--detail');
  if (hero) {
    hero.style.cssText = coverStyle(tender.cover_image_url);
  }

  setText('detailTitle', tender.title || 'Aanbesteding');
  setText('detailMeta', [
    tender.client || 'Onbekende opdrachtgever',
    tender.location || 'Suriname',
    tender.submission_deadline ? `Deadline ${formatDate(tender.submission_deadline)}` : 'Deadline volgt',
  ].join(' • '));
  setText('detailBody', tender.description || 'Geen details beschikbaar.');
  setText('detailEligibility', tender.eligibility || 'Geschiktheidseisen volgen via de officiële bron of bij publicatie van de aanbestedingsstukken.');

  const facts = document.getElementById('detailFacts');
  if (facts) {
    facts.innerHTML = [
      factItem('Sector', tender.sector || 'Algemeen'),
      factItem('Contracttype', tender.contract_type || 'Tender'),
      factItem('Referentie', tender.reference_code || 'Nog niet opgegeven'),
      factItem('Budget', tender.budget_label || 'Op aanvraag'),
      factItem('Bron', tender.source_name || tender.client || 'Bron volgt'),
      factItem('Publicatiedatum', formatDate(tender.date)),
    ].join('');
  }

  const attachments = document.getElementById('detailAttachments');
  if (attachments) {
    const items = [];
    if (sourceUrl) {
      items.push(`
        <a class="detail__attachment" href="${sourceUrl}" target="_blank" rel="noopener">
          <span>Open officiële bron</span>
          <span>↗</span>
        </a>
      `);
    }
    if (Array.isArray(tender.attachment_urls)) {
      tender.attachment_urls.forEach((url, index) => {
        const safeUrl = safeExternalUrl(url);
        if (!safeUrl) return;
        items.push(`
          <a class="detail__attachment" href="${safeUrl}" target="_blank" rel="noopener">
            <span>Bijlage ${index + 1}</span>
            <span>↗</span>
          </a>
        `);
      });
    }
    attachments.innerHTML = items.length
      ? items.join('')
      : '<div class="detail__attachment"><span>Geen externe bijlagen gekoppeld.</span><span></span></div>';
  }

  const link = document.getElementById('detailLink');
  if (sourceUrl) {
    link?.classList.remove('hidden');
    if (link) link.href = sourceUrl;
  } else {
    link?.classList.add('hidden');
  }
}

function factItem(label, value) {
  return `
    <div class="detail__item">
      <p>${escapeHtml(label)}</p>
      <p>${escapeHtml(value)}</p>
    </div>
  `;
}

function formatDate(value) {
  if (!value) return 'Onbekende datum';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('nl-NL', { day: '2-digit', month: 'short', year: 'numeric' });
}

function coverStyle(value) {
  const url = safeExternalUrl(value);
  if (url) {
    return `background-image: linear-gradient(180deg, rgba(10,79,74,0.40), rgba(20,40,31,0.88)), url('${url}'); background-size: cover; background-position: center;`;
  }
  return '';
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

function escapeHtml(value) {
  const str = String(value ?? '');
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function safeExternalUrl(value) {
  if (!value) return '';
  try {
    const url = new URL(String(value), window.location.origin);
    if (url.protocol === 'http:' || url.protocol === 'https:') {
      return url.href;
    }
    return '';
  } catch {
    return '';
  }
}
