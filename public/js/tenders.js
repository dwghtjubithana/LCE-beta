const API_BASE = '/api';
const token = localStorage.getItem('lce_token');

const state = {
  tenders: [],
};

document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  document.getElementById('refreshBtn')?.addEventListener('click', fetchTenders);
  document.getElementById('applyFilters')?.addEventListener('click', renderTenders);
  document.getElementById('filterQuery')?.addEventListener('input', renderTenders);
  document.getElementById('filterSort')?.addEventListener('change', renderTenders);
  document.getElementById('filterCategory')?.addEventListener('change', renderTenders);
  bindModal();
  fetchTenders();
});

async function fetchTenders() {
  const statusEl = document.getElementById('feedStatus');
  if (statusEl) statusEl.textContent = 'Tenders laden...';
  const endpoint = token ? `${API_BASE}/tenders` : `${API_BASE}/public/tenders`;

  try {
    const res = await fetch(endpoint, {
      headers: token ? { 'Authorization': `Bearer ${token}` } : {},
    });
    if (!res.ok) throw new Error('Kan tenders niet laden');
    const data = await res.json();
    state.tenders = Array.isArray(data?.tenders) ? data.tenders : [];
    updateStats(state.tenders);
    renderTenders();
    if (statusEl) statusEl.textContent = 'Tenders bijgewerkt.';
  } catch (err) {
    if (statusEl) statusEl.textContent = 'Fout bij laden tenders.';
  }
}

function renderTenders() {
  const grid = document.getElementById('tenderGrid');
  if (!grid) return;
  const query = (document.getElementById('filterQuery')?.value || '').toLowerCase();
  const category = document.getElementById('filterCategory')?.value || 'all';
  const sort = document.getElementById('filterSort')?.value || 'new';

  let tenders = [...state.tenders];
  if (query) {
    tenders = tenders.filter((t) => `${t.title} ${t.client}`.toLowerCase().includes(query));
  }
  if (category !== 'all') {
    tenders = tenders.filter((t) => matchCategory(t, category));
  }
  tenders.sort((a, b) => {
    const da = new Date(a.date || 0).getTime();
    const db = new Date(b.date || 0).getTime();
    return sort === 'new' ? db - da : da - db;
  });

  if (tenders.length === 0) {
    grid.innerHTML = '<div class="card">Geen tenders gevonden.</div>';
    return;
  }

  grid.innerHTML = '';
  tenders.forEach((tender) => {
    const gated = isGated(tender);
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML = `
      <div class="card__meta">${formatDate(tender.date)} · ${escapeHtml(tender.project || 'Tender')}</div>
      <div class="card__title">${escapeHtml(tender.title || 'Aanbesteding')}</div>
      <div class="card__client">${escapeHtml(tender.client || 'Onbekende opdrachtgever')}</div>
      <div class="card__tag">${tender.is_direct_work ? 'Direct werk' : gated ? 'Upgrade nodig' : 'Volledig zichtbaar'}</div>
      <div class="${gated ? 'card__blur' : ''}">
        <p>${escapeHtml(tender.description || 'Geen beschrijving beschikbaar.')}</p>
      </div>
      ${gated ? '<div class="card__cta">Upgrade naar Business om details te zien</div>' : ''}
    `;
    const link = document.createElement('a');
    link.href = `/tenders/${tender.id}`;
    link.className = 'card__link';
    link.appendChild(card);
    grid.appendChild(link);
  });
}

function updateStats(tenders) {
  const total = tenders.length;
  const today = new Date().toDateString();
  const todayCount = tenders.filter((t) => new Date(t.date || 0).toDateString() === today).length;
  const directCount = tenders.filter((t) => t.is_direct_work).length;

  setText('statTotal', total);
  setText('statToday', todayCount);
  setText('statDirect', directCount);
}

function openModal(tender) {
  const modal = document.getElementById('tenderModal');
  if (!modal) return;
  const gated = isGated(tender);

  setText('modalMeta', `${formatDate(tender.date)} · ${tender.project || 'Tender'}`);
  setText('modalTitle', tender.title || 'Aanbesteding');
  setText('modalClient', tender.client || 'Onbekende opdrachtgever');
  setText('modalBody', tender.description || 'Geen details beschikbaar.');

  const link = document.getElementById('modalLink');
  const upgrade = document.getElementById('modalUpgrade');

  if (gated) {
    link?.classList.add('hidden');
    upgrade?.classList.remove('hidden');
  } else {
    link?.classList.remove('hidden');
    upgrade?.classList.add('hidden');
    if (link) link.href = tender.details_url || '#';
  }

  modal.classList.remove('hidden');
}

function bindModal() {
  const modal = document.getElementById('tenderModal');
  if (!modal) return;
  modal.addEventListener('click', (e) => {
    if (e.target.hasAttribute('data-modal-close')) {
      modal.classList.add('hidden');
    }
  });
}

function isGated(tender) {
  const bullet = typeof tender.description === 'string' && /•/.test(tender.description);
  return !tender.details_url && !tender.attachments && bullet;
}

function matchCategory(tender, category) {
  const text = `${tender.title} ${tender.project} ${tender.client}`.toLowerCase();
  if (category === 'direct') return Boolean(tender.is_direct_work);
  if (category === 'oil') return text.includes('oil') || text.includes('olie') || text.includes('gas');
  if (category === 'government') return text.includes('overheid') || text.includes('ministerie');
  if (category === 'construction') return text.includes('bouw') || text.includes('construct');
  return true;
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

function escapeHtml(value) {
  const str = String(value ?? '');
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
