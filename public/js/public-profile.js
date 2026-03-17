const API_BASE = '/api';

document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  const root = document.querySelector('[data-slug]');
  const slug = root?.getAttribute('data-slug');
  if (!slug) return;
  fetchProfile(slug);

  document.getElementById('shareBtn')?.addEventListener('click', () => {
    const url = window.location.href;
    const text = encodeURIComponent(`Bekijk mijn SuriCore Digital ID: ${url}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
  });
});

async function fetchProfile(slug) {
  try {
    const res = await fetch(`${API_BASE}/public/companies/${slug}`);
    if (!res.ok) throw new Error('Niet gevonden');
    const data = await res.json();
    renderProfile(data.profile);
  } catch (err) {
    setText('profileName', 'Profiel niet gevonden');
  }
}

function renderProfile(profile) {
  setText('profileName', profile.display_name || profile.company_name || 'SuriCore Partner');
  setText('profileSector', profile.sector || '');
  setText('profileAddress', profile.address || 'Onbekend');

  const contact = profile.contact || {};
  const contactParts = [contact.email, contact.phone].filter(Boolean).join(' · ');
  setText('profileContact', contactParts || 'Niet beschikbaar');

  const status = (profile.verification_status || 'UNVERIFIED').toUpperCase();
  const badge = document.getElementById('statusBadge');
  if (badge) {
    const isStrong = status === 'VERIFIED_ENTITY' || status === 'OFFSHORE_READY' || status === 'GOLD';
    badge.textContent = isStrong ? 'GEVERIFIEERD' : 'BASIS';
    badge.classList.toggle('badge--gold', isStrong);
    badge.classList.toggle('badge--gray', !isStrong);
  }

  const avatar = document.getElementById('profileAvatar');
  if (avatar) {
    avatar.textContent = '';
    if (profile.photo_url) {
      const photoUrl = safeExternalUrl(profile.photo_url);
      if (photoUrl) {
        const img = document.createElement('img');
        img.src = photoUrl;
        img.alt = 'Profiel foto';
        avatar.appendChild(img);
      } else {
        const initial = (profile.display_name || profile.company_name || 'S').charAt(0).toUpperCase();
        avatar.textContent = initial;
      }
    } else {
      const initial = (profile.display_name || profile.company_name || 'S').charAt(0).toUpperCase();
      avatar.textContent = initial;
    }
  }

  renderMap(profile);
  renderSocialLinks(profile);
}

function renderMap(profile) {
  const map = document.getElementById('mapPlaceholder');
  if (!map) return;
  const lat = Number(profile.lat);
  const lng = Number(profile.lng);
  const hasCoords = Number.isFinite(lat) && Number.isFinite(lng);
  const address = profile.address || '';
  if (!hasCoords && !address) return;

  const query = hasCoords ? `${lat},${lng}` : String(address);
  const url = `https://maps.google.com/maps?q=${encodeURIComponent(query)}&z=14&output=embed`;
  map.textContent = '';
  const iframe = document.createElement('iframe');
  iframe.title = 'Locatie';
  iframe.src = url;
  iframe.width = '100%';
  iframe.height = '240';
  iframe.style.border = '0';
  iframe.style.borderRadius = '16px';
  iframe.loading = 'lazy';
  iframe.referrerPolicy = 'no-referrer-when-downgrade';
  map.appendChild(iframe);
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

function renderSocialLinks(profile) {
  const wrap = document.getElementById('socialLinks');
  if (!wrap) return;

  const contact = profile.contact || {};
  const links = [
    { key: 'website', label: 'Website', icon: 'globe' },
    { key: 'whatsapp', label: 'WhatsApp', icon: 'message-circle' },
    { key: 'facebook', label: 'Facebook', icon: 'facebook' },
    { key: 'linkedin', label: 'LinkedIn', icon: 'linkedin' },
  ].filter((item) => contact[item.key]);

  if (!links.length) {
    wrap.innerHTML = '<span class="status">Geen sociale links beschikbaar.</span>';
    return;
  }

  wrap.textContent = '';
  links.forEach((item) => {
    const href = safeExternalUrl(contact[item.key]);
    if (!href) return;
    const anchor = document.createElement('a');
    anchor.className = 'social-link';
    anchor.href = href;
    anchor.target = '_blank';
    anchor.rel = 'noopener noreferrer';
    anchor.innerHTML = `<i data-lucide="${item.icon}"></i> ${item.label}`;
    wrap.appendChild(anchor);
  });
  if (window.lucide) {
    window.lucide.createIcons();
  }
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

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
