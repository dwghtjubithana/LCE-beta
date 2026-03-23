const API_BASE = '/api';
const token = sessionStorage.getItem('lce_token') || localStorage.getItem('lce_token');

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
  bindGridActions();
  fetchTenders();
});

async function fetchTenders() {
  const statusEl = document.getElementById('feedStatus');
  if (statusEl) statusEl.textContent = 'Aanbestedingen laden...';
  const endpoint = token ? `${API_BASE}/tenders` : `${API_BASE}/public/tenders`;

  try {
    const res = await fetch(endpoint, {
      headers: token ? { 'Authorization': `Bearer ${token}` } : {},
    });
    if (!res.ok) throw new Error('Kan aanbestedingen niet laden');
    const data = await res.json();
    state.tenders = Array.isArray(data?.tenders) ? data.tenders : [];
    updateStats(state.tenders);
    renderFeatured(state.tenders);
    renderTenders();
    if (statusEl) statusEl.textContent = 'Feed bijgewerkt.';
  } catch (err) {
    renderEmpty(document.getElementById('tenderGrid'), 'De feed kon niet geladen worden.');
    renderEmpty(document.getElementById('featuredRail'), 'Geen uitgelichte items beschikbaar.');
    if (statusEl) statusEl.textContent = 'Fout bij laden van de feed.';
  }
}

function renderFeatured(items) {
  const rail = document.getElementById('featuredRail');
  if (!rail) return;

  const featured = [...items]
    .sort((a, b) => sortByDeadline(a, b))
    .slice(0, 3);

  if (!featured.length) {
    renderEmpty(rail, 'Nog geen featured items beschikbaar.');
    return;
  }

  rail.innerHTML = featured.map((tender) => `
    <article class="featured-card" style="${coverStyle(tender.cover_image_url)}">
      <div class="featured-card__content">
        <div class="featured-card__meta">
          ${renderChip(getSectorLabel(tender), 'soft')}
          ${renderChip(formatDeadlineChip(tender.submission_deadline), 'soft')}
        </div>
        <h3>${escapeHtml(tender.title || 'Aanbesteding')}</h3>
        <p>${escapeHtml(tender.description || 'Geen beschrijving beschikbaar.')}</p>
      </div>
    </article>
  `).join('');
}

function renderTenders() {
  const grid = document.getElementById('tenderGrid');
  if (!grid) return;

  const query = (document.getElementById('filterQuery')?.value || '').trim().toLowerCase();
  const category = document.getElementById('filterCategory')?.value || 'all';
  const sort = document.getElementById('filterSort')?.value || 'deadline';

  let tenders = [...state.tenders];

  if (query) {
    tenders = tenders.filter((tender) => `${tender.title} ${tender.client} ${tender.location} ${tender.sector}`.toLowerCase().includes(query));
  }

  if (category !== 'all') {
    tenders = tenders.filter((tender) => matchCategory(tender, category));
  }

  tenders.sort((a, b) => {
    if (sort === 'deadline') return sortByDeadline(a, b);
    const da = new Date(a.date || 0).getTime();
    const db = new Date(b.date || 0).getTime();
    return sort === 'new' ? db - da : da - db;
  });

  if (!tenders.length) {
    renderEmpty(grid, 'Geen aanbestedingen gevonden voor deze filters.');
    return;
  }

  grid.innerHTML = tenders.map((tender) => renderCard(tender)).join('');
  lucide.createIcons();
}

function renderCard(tender) {
  const title = escapeHtml(tender.title || 'Aanbesteding');
  const client = escapeHtml(tender.client || 'Onbekende opdrachtgever');
  const description = escapeHtml(tender.description || 'Geen omschrijving beschikbaar.');
  const detailUrl = `/tenders/${tender.id}`;
  const sourceUrl = safeExternalUrl(tender.details_url || tender.source_url);
  const logo = renderLogo(tender);
  const deadlineLabel = tender.submission_deadline ? formatDate(tender.submission_deadline) : 'Nog niet opgegeven';
  const budget = escapeHtml(tender.budget_label || 'Op aanvraag');
  const location = escapeHtml(tender.location || 'Suriname');

  return `
    <article class="card">
      <div class="card__media" style="${coverStyle(tender.cover_image_url)}">
        ${logo}
        ${renderChip(getAvailabilityLabel(tender), 'soft')}
      </div>
      <div class="card__body">
        <div class="card__meta">
          ${renderChip(getSectorLabel(tender), 'tint')}
          ${renderChip(escapeHtml(tender.contract_type || 'Tender'), 'muted')}
          ${tender.is_direct_work ? renderChip('Direct werk', 'warn') : ''}
        </div>
        <div class="card__headline">
          <div>
            <h3 class="card__title">${title}</h3>
            <p class="card__client">${client}</p>
          </div>
        </div>
        <p class="card__summary">${description}</p>
        <div class="card__facts">
          <div class="fact">
            <p class="fact__label">Deadline</p>
            <p class="fact__value">${escapeHtml(deadlineLabel)}</p>
          </div>
          <div class="fact">
            <p class="fact__label">Locatie</p>
            <p class="fact__value">${location}</p>
          </div>
          <div class="fact">
            <p class="fact__label">Budget</p>
            <p class="fact__value">${budget}</p>
          </div>
          <div class="fact">
            <p class="fact__label">Referentie</p>
            <p class="fact__value">${escapeHtml(tender.reference_code || 'Nog niet opgegeven')}</p>
          </div>
        </div>
        <div class="card__footer">
          <div class="card__actions">
            <button class="card__button" type="button" data-open-modal="${tender.id}">Quick view</button>
            <a class="card__inline-link" href="${detailUrl}">Volledige pagina</a>
          </div>
          ${sourceUrl
            ? `<a class="card__inline-link" href="${sourceUrl}" target="_blank" rel="noopener">Bron</a>`
            : '<span class="card__inline-link">Bron volgt</span>'}
        </div>
      </div>
    </article>
  `;
}

function bindGridActions() {
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-open-modal]');
    if (!trigger) return;
    const tenderId = Number(trigger.getAttribute('data-open-modal'));
    const tender = state.tenders.find((item) => Number(item.id) === tenderId);
    if (tender) {
      openModal(tender);
    }
  });
}

function updateStats(tenders) {
  const total = tenders.length;
  const now = new Date();
  const nextWeek = new Date();
  nextWeek.setDate(now.getDate() + 7);
  const deadlineCount = tenders.filter((tender) => {
    const deadline = new Date(tender.submission_deadline || tender.date || 0);
    return !Number.isNaN(deadline.getTime()) && deadline >= now && deadline <= nextWeek;
  }).length;
  const directCount = tenders.filter((tender) => Boolean(tender.is_direct_work)).length;

  setText('statTotal', total);
  setText('statToday', deadlineCount);
  setText('statDirect', directCount);
}

function openModal(tender) {
  const modal = document.getElementById('tenderModal');
  if (!modal) return;
  const sourceUrl = safeExternalUrl(tender.details_url || tender.source_url);

  setText('modalMeta', `${formatDate(tender.date)} · ${tender.project || tender.sector || 'Tender'}`);
  setText('modalTitle', tender.title || 'Aanbesteding');
  setText('modalClient', tender.client || 'Onbekende opdrachtgever');
  setText('modalBody', tender.description || 'Geen details beschikbaar.');
  setText('modalEligibility', tender.eligibility || 'Algemene geschiktheidseisen volgen bij publicatie van de bron.');

  const media = document.getElementById('modalMedia');
  if (media) {
    media.style.cssText = coverStyle(tender.cover_image_url);
  }

  const facts = document.getElementById('modalFacts');
  if (facts) {
    facts.innerHTML = [
      factMarkup('Sector', getSectorLabel(tender)),
      factMarkup('Locatie', tender.location || 'Suriname'),
      factMarkup('Contract', tender.contract_type || 'Tender'),
      factMarkup('Deadline', tender.submission_deadline ? formatDate(tender.submission_deadline) : 'Nog niet opgegeven'),
      factMarkup('Budget', tender.budget_label || 'Op aanvraag'),
      factMarkup('Referentie', tender.reference_code || 'Nog niet opgegeven'),
    ].join('');
  }

  const link = document.getElementById('modalLink');
  const detailLink = document.getElementById('modalDetailLink');

  if (detailLink) detailLink.href = `/tenders/${tender.id}`;

  if (sourceUrl) {
    link?.classList.remove('hidden');
    if (link) link.href = sourceUrl;
  } else {
    link?.classList.add('hidden');
  }

  modal.classList.remove('hidden');
}

function bindModal() {
  const modal = document.getElementById('tenderModal');
  if (!modal) return;

  modal.addEventListener('click', (event) => {
    if (event.target.hasAttribute('data-modal-close')) {
      modal.classList.add('hidden');
    }
  });
}

function matchCategory(tender, category) {
  const sector = String(tender.sector || '').toLowerCase();
  const text = `${tender.title} ${tender.project} ${tender.client} ${tender.location} ${sector}`.toLowerCase();
  if (category === 'direct') return Boolean(tender.is_direct_work);
  if (category === 'oil') return sector.includes('olie') || sector.includes('gas') || text.includes('staatsolie');
  if (category === 'government') return sector.includes('overheid') || text.includes('ministerie') || text.includes('gov');
  if (category === 'mining') return sector.includes('mijn') || text.includes('rosebel') || text.includes('gold');
  if (category === 'construction') return sector.includes('bouw') || sector.includes('infra') || text.includes('civil');
  return true;
}

function sortByDeadline(a, b) {
  const da = new Date(a.submission_deadline || a.date || 0).getTime();
  const db = new Date(b.submission_deadline || b.date || 0).getTime();
  if (!da && !db) return 0;
  if (!da) return 1;
  if (!db) return -1;
  return da - db;
}

function getSectorLabel(tender) {
  return escapeHtml(tender.sector || 'Algemeen');
}

function getAvailabilityLabel(tender) {
  if (tender.is_direct_work) return 'Snelle inzet';
  if (tender.submission_deadline) return `Deadline ${formatDate(tender.submission_deadline)}`;
  return 'Actieve listing';
}

function formatDeadlineChip(value) {
  if (!value) return 'Deadline volgt';
  return `Deadline ${formatDate(value)}`;
}

function coverStyle(value) {
  const url = safeExternalUrl(value);
  if (url) {
    return `background-image: linear-gradient(180deg, rgba(15,118,110,0.10), rgba(20,40,31,0.62)), url('${url}');`;
  }
  return 'background-image: linear-gradient(135deg, #0f766e, #14281f);';
}

function renderLogo(tender) {
  const url = safeExternalUrl(tender.issuer_logo_url);
  if (url) {
    return `<span class="card__logo"><img src="${url}" alt="${escapeHtml(tender.client || 'Logo')}"></span>`;
  }
  return `<span class="card__logo">${escapeHtml(initials(tender.client || tender.title || 'SC'))}</span>`;
}

function initials(value) {
  return String(value || '')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || 'SC';
}

function factMarkup(label, value) {
  return `
    <div class="fact">
      <p class="fact__label">${escapeHtml(label)}</p>
      <p class="fact__value">${escapeHtml(value)}</p>
    </div>
  `;
}

function renderChip(label, variant) {
  return `<span class="chip chip--${variant}">${label}</span>`;
}

function renderEmpty(target, message) {
  if (!target) return;
  target.innerHTML = `<div class="empty-state">${escapeHtml(message)}</div>`;
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
