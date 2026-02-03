const API_BASE = '/api';
let authToken = localStorage.getItem('lce_token');
let currentUser = null;
let currentCompany = null;

document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  initUploadUi();

  // Strikte auth check
  if (authToken) {
    initializeSession();
  } else {
    showLoginModal();
  }
});

function initUploadUi() {
  const uploadBtn = document.getElementById('uploadBtn');
  const cameraBtn = document.getElementById('cameraBtn');
  const fileInput = document.getElementById('fileInput');
  const cameraInput = document.getElementById('cameraInput');
  const dropzone = document.getElementById('uploadDropzone');
  const actionCta = document.getElementById('actionRequiredCta');
  const paymentBtn = document.getElementById('paymentUploadBtn');
  const paymentInput = document.getElementById('paymentInput');
  const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
  const profilePhotoInput = document.getElementById('profilePhotoInput');
  const geocodeBtn = document.getElementById('geocodeBtn');
  const slugInput = document.getElementById('publicSlugInput');

  uploadBtn?.addEventListener('click', () => fileInput?.click());
  cameraBtn?.addEventListener('click', () => cameraInput?.click());
  actionCta?.addEventListener('click', () => {
    document.getElementById('documentsSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  paymentBtn?.addEventListener('click', () => paymentInput?.click());
  paymentInput?.addEventListener('change', handlePaymentProof);
  uploadPhotoBtn?.addEventListener('click', () => profilePhotoInput?.click());
  profilePhotoInput?.addEventListener('change', handleProfilePhotoUpload);
  geocodeBtn?.addEventListener('click', handleGeocode);
  slugInput?.addEventListener('blur', handleSlugCheck);

  dropzone?.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('border-blue-400', 'bg-blue-50');
  });

  dropzone?.addEventListener('dragleave', () => {
    dropzone.classList.remove('border-blue-400', 'bg-blue-50');
  });

  dropzone?.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('border-blue-400', 'bg-blue-50');
    const file = e.dataTransfer?.files?.[0];
    if (file) {
      updateUploadFilename(file.name);
      handleFileUpload(file);
    }
  });
}

function handleFileInputChange(e) {
  const file = e.target.files?.[0];
  if (file) {
    updateUploadFilename(file.name);
    handleFileUpload(file);
  }
  e.target.value = '';
}

function showLoginModal() {
  document.getElementById('loginModal').classList.remove('hidden');
  document.getElementById('loginModal').classList.remove('modal-enter');
  document.getElementById('loginModal').classList.add('modal-active');
}

// --- AUTHENTICATIE ---

async function initializeSession() {
  try {
    // 1. Get User Info
    const userRes = await fetch(`${API_BASE}/auth/me`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    });

    if (userRes.status === 401 || userRes.status === 403) {
      throw new Error('Sessie verlopen');
    }
    if (!userRes.ok) throw new Error('Fout bij ophalen gebruikersgegevens');

    const userData = await userRes.json();
    currentUser = userData?.user;

    // UI Check op user data
    if (!currentUser) throw new Error('Lege user data ontvangen');

    // Update UI User
    document.getElementById('userNameDisplay').textContent = currentUser.username || currentUser.email || 'Onbekend';
    const initial = (currentUser.username || currentUser.email || '?').charAt(0).toUpperCase();
    document.getElementById('userInitials').textContent = initial;
    updatePlanBadges(currentUser);

    // Hide Modal & Unlock UI
    document.getElementById('loginModal').classList.add('hidden');
    document.getElementById('dashboardContent').style.opacity = '1';
    document.getElementById('dashboardContent').style.pointerEvents = 'auto';
    document.getElementById('connectionStatus').classList.remove('hidden');

    // 2. Load Company
    const companyRes = await fetch(`${API_BASE}/companies/me`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    });
    if (!companyRes.ok) {
      showToast('LET OP: Geen company gevonden voor deze gebruiker.', 'error');
      return;
    }
    const companyData = await companyRes.json();
    const company = companyData?.company;
    if (!company) {
      showToast('LET OP: Geen company gevonden voor deze gebruiker.', 'error');
      return;
    }
    currentCompany = company;
    populateCompanyForm(company);
    populateDigitalIdForm(company);

    fetchDashboard();
    fetchDocuments();
    fetchNotifications();
  } catch (err) {
    console.error(err);
    handleLogout(); // Bij twijfel: uitloggen
  }
}

document.getElementById('companyProfileForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const statusEl = document.getElementById('profileStatus');
  const btn = document.getElementById('saveProfileBtn');

  const payload = {
    company_name: document.getElementById('companyNameInput').value.trim(),
    sector: document.getElementById('companySectorInput').value.trim(),
    experience: document.getElementById('companyExperienceInput').value.trim(),
    contact: {
      email: document.getElementById('companyContactInput').value.trim(),
    },
  };

  btn.disabled = true;
  btn.classList.add('opacity-70');
  statusEl.textContent = 'Opslaan...';

  try {
    if (!currentCompany?.id) throw new Error('Geen company beschikbaar');
    const res = await fetch(`${API_BASE}/companies/${currentCompany.id}`, {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Opslaan mislukt');

    currentCompany = data.company || currentCompany;
    statusEl.textContent = 'Opgeslagen.';
    showToast('Bedrijfsprofiel bijgewerkt', 'success');
  } catch (err) {
    statusEl.textContent = 'Opslaan mislukt.';
    showToast(err.message || 'Opslaan mislukt', 'error');
  } finally {
    btn.disabled = false;
    btn.classList.remove('opacity-70');
  }
});

document.getElementById('digitalIdForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const statusEl = document.getElementById('digitalIdStatus');
  const btn = document.getElementById('saveDigitalIdBtn');

  const payload = {
    public_slug: document.getElementById('publicSlugInput').value.trim().toLowerCase(),
    display_name: document.getElementById('displayNameInput').value.trim(),
    address: document.getElementById('addressInput').value.trim(),
    lat: document.getElementById('latInput').value.trim() || null,
    lng: document.getElementById('lngInput').value.trim() || null,
  };

  btn.disabled = true;
  btn.classList.add('opacity-70');
  statusEl.textContent = 'Opslaan...';

  try {
    if (!currentCompany?.id) throw new Error('Geen company beschikbaar');
    const res = await fetch(`${API_BASE}/companies/${currentCompany.id}`, {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Opslaan mislukt');

    currentCompany = data.company || currentCompany;
    populateDigitalIdForm(currentCompany);
    statusEl.textContent = 'Opgeslagen.';
    showToast('Digital ID bijgewerkt', 'success');
  } catch (err) {
    statusEl.textContent = err.message || 'Opslaan mislukt.';
    showToast(err.message || 'Opslaan mislukt', 'error');
  } finally {
    btn.disabled = false;
    btn.classList.remove('opacity-70');
  }
});

document.getElementById('downloadProfileBtn').addEventListener('click', async () => {
  try {
    const res = await fetch(`${API_BASE}/companies/me/profile.pdf`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    });
    if (!res.ok) throw new Error('Download mislukt');

    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'suricore-profiel.pdf';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  } catch (err) {
    showToast(err.message || 'Download mislukt', 'error');
  }
});

document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = document.getElementById('emailInput').value;
  const password = document.getElementById('passwordInput').value;
  const btn = document.getElementById('loginBtn');
  const errorDiv = document.getElementById('loginError');

  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" class="animate-spin w-4 h-4"></i> Verifiëren...';
  lucide.createIcons();
  errorDiv.classList.add('hidden');

  try {
    const res = await fetch(`${API_BASE}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    const data = await res.json();

    if (!res.ok) throw new Error(data.message || 'Ongeldige inloggegevens');
    if (!data.token) throw new Error('Geen token ontvangen van server');

    authToken = data.token;
    localStorage.setItem('lce_token', authToken);

    // Restart flow
    initializeSession();
  } catch (err) {
    errorDiv.textContent = err.message;
    errorDiv.classList.remove('hidden');
    btn.disabled = false;
    btn.textContent = 'Inloggen';
  }
});

function handleLogout() {
  localStorage.removeItem('lce_token');
  location.reload();
}

// --- DASHBOARD DATA ---

async function fetchDashboard() {
  try {
    const res = await fetch(`${API_BASE}/companies/me/dashboard`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    });

    if (!res.ok) {
      document.getElementById('scoreMessage').textContent = `Server Fout: ${res.status}`;
      document.getElementById('scoreMessage').classList.add('text-red-500');
      return;
    }

    const data = await res.json();

    // Update Gauge
    updateGauge(data.current_score);

    // Update Stats from required_documents
    const required = Array.isArray(data.required_documents) ? data.required_documents : [];
    updateCategoryOptions(required);
    const counts = {
      valid: 0,
      review: 0,
      processing: 0,
      invalid: 0,
      missing: 0,
    };
    required.forEach((doc) => {
      const status = (doc.status || 'MISSING').toUpperCase();
      if (status === 'VALID') counts.valid += 1;
      else if (status === 'PROCESSING') counts.processing += 1;
      else if (status === 'INVALID' || status === 'EXPIRED') counts.invalid += 1;
      else if (status === 'REVIEW' || status === 'EXPIRING' || status === 'EXPIRING_SOON' || status === 'MANUAL_REVIEW' || status === 'NEEDS_CONFIRMATION') counts.review += 1;
      else counts.missing += 1;
    });

    document.getElementById('statValid').textContent = counts.valid;
    document.getElementById('statReview').textContent = counts.review + counts.missing;
    document.getElementById('statProcessing').textContent = counts.processing;
    document.getElementById('statInvalid').textContent = counts.invalid;

    // Update Message
    const msgEl = document.getElementById('scoreMessage');
    if (data.current_score >= 91) msgEl.textContent = 'Uitstekend! Uw compliance is op orde.';
    else if (data.current_score >= 51) msgEl.textContent = 'Goed bezig, maar er ontbreken nog documenten.';
    else msgEl.textContent = 'Let op: Uw compliance score is kritiek laag.';
  } catch (err) {
    console.error('Dashboard fetch error', err);
    document.getElementById('scoreMessage').textContent = 'Kan dashboard niet laden.';
  }
}

function updateGauge(score) {
  // Veiligheidscheck
  if (score === undefined || score === null) score = 0;

  const circle = document.getElementById('gaugeProgress');
  const display = document.getElementById('scoreDisplay');
  const label = document.getElementById('scoreLabel');

  circle.classList.remove('text-green-500', 'text-orange-500', 'text-red-500', 'text-slate-300');

  let colorClass = 'text-red-500';
  let labelText = 'KRITIEK';
  let bgClass = 'bg-red-50 text-red-600';

  if (score >= 91) {
    colorClass = 'text-green-500';
    labelText = 'GROEN';
    bgClass = 'bg-green-50 text-green-600';
  } else if (score >= 51) {
    colorClass = 'text-orange-500';
    labelText = 'ORANJE';
    bgClass = 'bg-orange-50 text-orange-600';
  }

  circle.classList.add(colorClass);
  label.className = `text-xs font-bold px-2 py-1 rounded mt-2 ${bgClass}`;
  label.textContent = labelText;

  // Dash Calculation
  const circumference = 2 * Math.PI * 40;
  const offset = circumference - (score / 100) * circumference;
  circle.style.strokeDashoffset = offset;
  display.textContent = `${score}%`;
}

// --- DOCUMENTS LIST ---

async function fetchDocuments() {
  const tbody = document.getElementById('documentsTableBody');
  try {
    const res = await fetch(`${API_BASE}/companies/me/documents`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    });

    if (!res.ok) {
      tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-8 text-center text-red-500 font-medium">Server fout (${res.status}): Kan documenten niet ophalen.</td></tr>`;
      return;
    }

    const data = await res.json();
    const docs = Array.isArray(data?.documents) ? data.documents : [];

    tbody.innerHTML = '';

    if (docs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Geen documenten gevonden in de database.</td></tr>';
      return;
    }

    updateActionRequiredPanel(docs);
    docs.forEach((doc) => {
      const statusUI = getStatusUI(doc.status);
      const label = doc.ui_label || statusUI.label;
      const action = doc.recommended_action || 'Bekijk';
      const safeTitle = escapeHtml(doc.title || doc.original_filename || 'Naamloos Document');
      const safeLabel = escapeHtml(label);
      const safeAction = escapeHtml(action);
      const safeDate = escapeHtml(doc.updated_at || '-');
      const docId = doc.id || doc.uuid || Math.random().toString(36).slice(2);
      const aiReason = escapeHtml(doc.extracted_data?.ai_reason || '');
      const aiFix = escapeHtml(doc.extracted_data?.ai_fix || '');
      const aiFeedback = escapeHtml(doc.ai_feedback || 'Geen AI-advies beschikbaar.');
      const detectedType = escapeHtml(doc.detected_type || doc.category_selected || 'Onbekend');
      const expiry = escapeHtml(doc.expiry_date || '-');
      const ocr = doc.ocr_confidence !== null && doc.ocr_confidence !== undefined ? `${doc.ocr_confidence}%` : '-';
      const ai = doc.ai_confidence !== null && doc.ai_confidence !== undefined ? `${doc.ai_confidence}%` : '-';
      const row = `
        <tr class="hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0" data-doc-id="${docId}" data-doc-status="${escapeHtml(doc.status || '')}">
          <td class="px-6 py-4 font-medium text-slate-800 flex items-center gap-3">
            <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
              <i data-lucide="file-text" class="w-4 h-4"></i>
            </div>
            ${safeTitle}
          </td>
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border ${statusUI.class}">
              ${safeLabel}
            </span>
          </td>
          <td class="px-6 py-4 text-slate-500">${safeDate}</td>
          <td class="px-6 py-4 text-right">
            <button class="text-slate-500 hover:text-blue-600 transition text-sm font-semibold" type="button" data-doc-action="${docId}">
              ${safeAction}
            </button>
          </td>
        </tr>
        <tr id="doc-details-${docId}" class="hidden bg-slate-50/60">
          <td colspan="4" class="px-6 py-4 text-sm text-slate-600">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Documenttype</p>
                <p class="font-medium text-slate-700">${detectedType}</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Expiry</p>
                <p class="font-medium text-slate-700">${expiry}</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Confidence</p>
                <p class="font-medium text-slate-700">OCR: ${ocr} · AI: ${ai}</p>
              </div>
            </div>
            <div class="mt-3 p-3 rounded-lg border border-slate-200 bg-white">
              <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">AI Advies</p>
              <p class="text-slate-700">${aiFeedback}</p>
              ${aiReason ? `<p class=\"text-xs text-slate-500 mt-2\">Reden: ${aiReason}</p>` : ''}
              ${aiFix ? `<p class=\"text-xs text-slate-500 mt-1\">Fix: ${aiFix}</p>` : ''}
            </div>
          </td>
        </tr>
      `;
      tbody.innerHTML += row;
    });
    lucide.createIcons();
    bindDocumentRowToggles();
  } catch (err) {
    console.error(err);
    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-red-500">Netwerkfout bij laden documenten.</td></tr>';
  }
}

function getStatusUI(status) {
  // Mapping based on API Docs Section 7
  switch (status?.toUpperCase()) {
    case 'VALID':
      return { label: 'VALID', class: 'bg-green-50 text-green-700 border-green-200' };
    case 'PROCESSING':
      return { label: 'PROCESSING', class: 'bg-blue-50 text-blue-700 border-blue-200' };
    case 'INVALID':
    case 'EXPIRED':
      return { label: 'INVALID', class: 'bg-red-50 text-red-700 border-red-200' };
    case 'REVIEW':
    case 'EXPIRING':
    case 'EXPIRING_SOON':
    case 'MANUAL_REVIEW':
    case 'NEEDS_CONFIRMATION':
      return { label: 'REVIEW', class: 'bg-orange-50 text-orange-700 border-orange-200' };
    case 'MISSING':
      return { label: 'MISSING', class: 'bg-gray-100 text-gray-600 border-gray-200' };
    default:
      return { label: 'MISSING', class: 'bg-gray-100 text-gray-600 border-gray-200' };
  }
}

// --- FILE UPLOAD ---

async function handleFileUpload(file) {
  if (!file) return;

  showToast(`Uploaden: ${file.name}...`, 'info');

  const formData = new FormData();
  formData.append('file', file);
  const category = document.getElementById('categorySelect')?.value;
  if (category) formData.append('category_selected', category);

  try {
    const res = await fetch(`${API_BASE}/documents/upload`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${authToken}` },
      body: formData
    });

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}));
      if (res.status === 403 && errData.code === 'PLAN_RESTRICTED') {
        showToast('Upgrade nodig voor AI-analyse.', 'error');
        showUpgradePanelNotice();
        return;
      }
      throw new Error(errData.message || 'Server weigert upload');
    }

    showToast('Bestand succesvol geüpload', 'success');

    // Refresh data
    fetchDocuments();
    fetchDashboard();
  } catch (err) {
    showToast(`Fout: ${err.message}`, 'error');
    console.error(err);
  }
}

function showToast(msg, type = 'info') {
  const toast = document.getElementById('toast');
  const msgEl = document.getElementById('toastMsg');
  const iconEl = document.getElementById('toastIcon');

  msgEl.textContent = msg;

  // Styles reset
  toast.className = 'fixed bottom-6 right-6 px-6 py-4 rounded-xl shadow-2xl transform transition-transform duration-300 flex items-center gap-3 z-50 text-white';

  if (type === 'success') {
    toast.classList.add('bg-green-600');
    iconEl.setAttribute('data-lucide', 'check-circle');
  } else if (type === 'error') {
    toast.classList.add('bg-red-600');
    iconEl.setAttribute('data-lucide', 'x-circle');
  } else {
    toast.classList.add('bg-slate-800');
    iconEl.setAttribute('data-lucide', 'loader-2');
  }

  lucide.createIcons();
  toast.classList.remove('translate-y-32'); // Slide in

  setTimeout(() => toast.classList.add('translate-y-32'), 3000); // Slide out
}

function populateCompanyForm(company) {
  document.getElementById('companyNameInput').value = company.company_name || '';
  document.getElementById('companySectorInput').value = company.sector || '';
  document.getElementById('companyExperienceInput').value = company.experience || '';
  document.getElementById('companyContactInput').value = company.contact?.email || '';
}

function populateDigitalIdForm(company) {
  document.getElementById('publicSlugInput').value = company.public_slug || '';
  document.getElementById('displayNameInput').value = company.display_name || '';
  document.getElementById('addressInput').value = company.address || '';
  document.getElementById('latInput').value = company.lat || '';
  document.getElementById('lngInput').value = company.lng || '';

  const link = document.getElementById('publicProfileLink');
  if (link) {
    const slug = company.public_slug || '';
    link.href = slug ? `/p/${slug}` : '#';
    link.textContent = slug ? `Bekijk publieke link: /p/${slug}` : 'Stel je slug in om de link te zien';
  }
}

function bindDocumentRowToggles() {
  const tbody = document.getElementById('documentsTableBody');
  if (!tbody || tbody.dataset.togglesBound === 'true') return;
  tbody.dataset.togglesBound = 'true';

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-doc-action]');
    if (!btn) return;
    const docId = btn.getAttribute('data-doc-action');
    const row = btn.closest('tr');
    const status = row?.getAttribute('data-doc-status') || '';

    if (status.toUpperCase() === 'NEEDS_CONFIRMATION') {
      handleDocumentConfirmation(docId);
      return;
    }
    const details = document.getElementById(`doc-details-${docId}`);
    if (details) details.classList.toggle('hidden');
  });
}

function updateCategoryOptions(requiredDocs) {
  const select = document.getElementById('categorySelect');
  if (!select) return;
  const options = Array.isArray(requiredDocs) ? requiredDocs : [];
  const unique = [...new Set(options.map((item) => item.type).filter(Boolean))];

  select.innerHTML = '<option value="">Kies documenttype</option>';
  unique.forEach((type) => {
    const opt = document.createElement('option');
    opt.value = type;
    opt.textContent = type;
    select.appendChild(opt);
  });
}

function updateUploadFilename(name) {
  const el = document.getElementById('uploadFilename');
  if (el) el.textContent = name ? `Geselecteerd: ${name}` : '';
}

async function handleDocumentConfirmation(docId) {
  const select = document.getElementById('categorySelect');
  const category = select?.value;
  if (!category) {
    showToast('Kies eerst een documenttype in de dropdown.', 'error');
    return;
  }

  try {
    const res = await fetch(`${API_BASE}/documents/${docId}/confirm`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ category_selected: category }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Bevestigen mislukt');

    showToast('Document bevestigd en opnieuw verwerkt.', 'success');
    fetchDocuments();
    fetchDashboard();
  } catch (err) {
    showToast(err.message || 'Bevestigen mislukt', 'error');
  }
}

async function handleProfilePhotoUpload(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  const statusEl = document.getElementById('digitalIdStatus');
  if (statusEl) statusEl.textContent = `Uploaden: ${file.name}...`;

  try {
    const formData = new FormData();
    formData.append('file', file);

    const res = await fetch(`${API_BASE}/companies/me/profile-photo`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${authToken}` },
      body: formData,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Upload mislukt');

    showToast('Profielfoto geüpload', 'success');
    if (statusEl) statusEl.textContent = 'Profielfoto geüpload.';
  } catch (err) {
    showToast(err.message || 'Upload mislukt', 'error');
    if (statusEl) statusEl.textContent = 'Upload mislukt.';
  }
  e.target.value = '';
}

async function handleSlugCheck() {
  const input = document.getElementById('publicSlugInput');
  const statusEl = document.getElementById('digitalIdStatus');
  const slug = input?.value.trim().toLowerCase();
  if (!slug) return;

  try {
    const res = await fetch(`${API_BASE}/companies/slug-check?slug=${encodeURIComponent(slug)}`, {
      headers: { 'Authorization': `Bearer ${authToken}` },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Slug check mislukt');
    if (data.available) {
      if (statusEl) statusEl.textContent = 'Slug beschikbaar.';
    } else {
      if (statusEl) statusEl.textContent = 'Slug is al in gebruik.';
    }
  } catch (err) {
    if (statusEl) statusEl.textContent = err.message || 'Slug check mislukt.';
  }
}

async function handleGeocode() {
  const address = document.getElementById('addressInput')?.value.trim();
  const statusEl = document.getElementById('geocodeStatus');
  if (!address) {
    if (statusEl) statusEl.textContent = 'Vul eerst een adres in.';
    return;
  }
  if (statusEl) statusEl.textContent = 'Locatie zoeken...';

  try {
    const res = await fetch(`${API_BASE}/geocode`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ address }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Geocode mislukt');
    document.getElementById('latInput').value = data.lat || '';
    document.getElementById('lngInput').value = data.lng || '';
    if (statusEl) statusEl.textContent = 'Locatie ingevuld.';
  } catch (err) {
    if (statusEl) statusEl.textContent = err.message || 'Geocode mislukt.';
  }
}

function updateActionRequiredPanel(docs) {
  const panel = document.getElementById('actionRequiredPanel');
  const msg = document.getElementById('actionRequiredMsg');
  if (!panel || !msg) return;

  const now = new Date();
  const cutoff = new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000);
  const expiring = docs.filter((doc) => {
    const status = (doc.status || '').toUpperCase();
    if (status === 'EXPIRED' || status === 'EXPIRING_SOON') return true;
    if (!doc.expiry_date) return false;
    const date = new Date(doc.expiry_date);
    if (Number.isNaN(date.getTime())) return false;
    return date <= cutoff;
  });

  if (expiring.length === 0) {
    panel.classList.add('hidden');
    return;
  }

  panel.classList.remove('hidden');
  msg.textContent = `${expiring.length} document(en) verlopen binnenkort. Werk deze bij om je score te behouden.`;
}

async function fetchNotifications() {
  try {
    const res = await fetch(`${API_BASE}/notifications`, {
      headers: { 'Authorization': `Bearer ${authToken}` },
    });
    if (!res.ok) return;
    const data = await res.json();
    const notifications = Array.isArray(data?.notifications) ? data.notifications : [];
    updateNotificationsPanel(notifications);
  } catch (err) {
    // best-effort
  }
}

function updateNotificationsPanel(notifications) {
  const panel = document.getElementById('actionRequiredPanel');
  const msg = document.getElementById('actionRequiredMsg');
  if (!panel || !msg) return;
  const expiring = notifications.filter((n) => n.type === 'EXPIRING_SOON');
  if (expiring.length === 0) return;
  panel.classList.remove('hidden');
  msg.textContent = `Je hebt ${expiring.length} waarschuwing(en) voor verlopen documenten.`;
}

function escapeHtml(value) {
  const str = String(value ?? '');
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function updatePlanBadges(user) {
  const planBadge = document.getElementById('planBadge');
  const statusBadge = document.getElementById('planStatusBadge');
  if (!planBadge || !statusBadge) return;

  const plan = (user?.plan || 'FREE').toUpperCase();
  const status = (user?.plan_status || 'ACTIVE').toUpperCase();
  planBadge.textContent = `PLAN: ${plan}`;
  statusBadge.textContent = `STATUS: ${status}`;

  const planClass = plan === 'BUSINESS'
    ? 'bg-green-100 text-green-700'
    : plan === 'PRO'
      ? 'bg-blue-100 text-blue-700'
      : 'bg-slate-200 text-slate-700';
  const statusClass = status === 'PENDING_PAYMENT'
    ? 'bg-orange-100 text-orange-700'
    : status === 'EXPIRED'
      ? 'bg-red-100 text-red-700'
      : 'bg-slate-200 text-slate-700';

  planBadge.className = `text-xs font-semibold px-3 py-1 rounded-full ${planClass}`;
  statusBadge.className = `text-xs font-semibold px-3 py-1 rounded-full ${statusClass}`;
}

function showUpgradePanelNotice() {
  const panel = document.getElementById('paymentStatus');
  if (!panel) return;
  panel.textContent = 'AI-analyse vereist een Pro-plan. Upload je betaalbewijs om te upgraden.';
}

async function handlePaymentProof(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  const statusEl = document.getElementById('paymentStatus');
  if (statusEl) statusEl.textContent = `Uploaden: ${file.name}...`;

  try {
    const formData = new FormData();
    formData.append('file', file);

    const res = await fetch(`${API_BASE}/payment-proofs`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${authToken}` },
      body: formData,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Upload mislukt');

    showToast('Betaalbewijs ontvangen. Admin zal dit beoordelen.', 'success');
    if (statusEl) statusEl.textContent = 'Betaalbewijs ontvangen. Status: Pending Payment.';
    if (data.user) updatePlanBadges(data.user);
  } catch (err) {
    showToast(err.message || 'Upload mislukt', 'error');
    if (statusEl) statusEl.textContent = 'Upload mislukt. Probeer opnieuw.';
  }
  e.target.value = '';
}
