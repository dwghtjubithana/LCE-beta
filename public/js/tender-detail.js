const API_BASE = '/api';
const token = localStorage.getItem('lce_token');

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
  const gated = isGated(tender);
  setText('detailTitle', tender.title || 'Aanbesteding');
  setText('detailMeta', `${formatDate(tender.date)} · ${tender.project || 'Tender'}`);
  setText('detailClient', tender.client || 'Onbekende opdrachtgever');
  setText('detailBody', tender.description || 'Geen details beschikbaar.');

  const link = document.getElementById('detailLink');
  const upgrade = document.getElementById('detailUpgrade');

  if (gated) {
    link?.classList.add('hidden');
    upgrade?.classList.remove('hidden');
  } else {
    link?.classList.remove('hidden');
    upgrade?.classList.add('hidden');
    if (link) link.href = tender.details_url || '#';
  }
}

function isGated(tender) {
  const bullet = typeof tender.description === 'string' && /•/.test(tender.description);
  return !tender.details_url && !tender.attachments && bullet;
}

function formatDate(value) {
  if (!value) return 'Onbekende datum';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('nl-NL', { day: '2-digit', month: 'short', year: 'numeric' });
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}
