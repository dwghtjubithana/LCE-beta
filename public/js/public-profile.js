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

  const status = (profile.verification_status || 'GRAY').toUpperCase();
  const badge = document.getElementById('statusBadge');
  if (badge) {
    badge.textContent = status === 'GOLD' ? 'GOUD' : 'GRIJS';
    badge.classList.toggle('badge--gold', status === 'GOLD');
    badge.classList.toggle('badge--gray', status !== 'GOLD');
  }

  const avatar = document.getElementById('profileAvatar');
  if (avatar) {
    if (profile.photo_url) {
      avatar.innerHTML = `<img src="${profile.photo_url}" alt="Profiel foto">`;
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
  const hasCoords = profile.lat && profile.lng;
  const address = profile.address || '';
  if (!hasCoords && !address) return;

  const query = hasCoords ? `${profile.lat},${profile.lng}` : encodeURIComponent(address);
  const url = `https://maps.google.com/maps?q=${query}&z=14&output=embed`;
  map.innerHTML = `<iframe title="Locatie" src="${url}" width="100%" height="240" style="border:0;border-radius:16px" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>`;
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

  wrap.innerHTML = links.map((item) => `
    <a class="social-link" href="${escapeHtml(contact[item.key])}" target="_blank" rel="noopener">
      <i data-lucide="${item.icon}"></i> ${item.label}
    </a>
  `).join('');
  if (window.lucide) {
    window.lucide.createIcons();
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
