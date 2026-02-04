const API_BASE = '/api';
let authToken = localStorage.getItem('lce_token');
let currentUser = null;
let currentCompany = null;
let pendingFrontFile = null;
let pendingBackFile = null;

document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  initUploadUi();

  // Strikte auth check
  if (authToken) {
    hideLoginModal();
    initializeSession();
  } else {
    showLoginModal();
  }
});

function initUploadUi() {
  const uploadBtn = document.getElementById('uploadBtn');
  const cameraBtn = document.getElementById('cameraBtn');
  const fileInput = document.getElementById('fileInput');
  const backFileInput = document.getElementById('backFileInput');
  const cameraInput = document.getElementById('cameraInput');
  const uploadBackBtn = document.getElementById('uploadBackBtn');
  const categorySelect = document.getElementById('categorySelect');
  const idSubtypeSelect = document.getElementById('idSubtypeSelect');
  const dropzone = document.getElementById('uploadDropzone');
  const actionCta = document.getElementById('actionRequiredCta');
  const paymentBtn = document.getElementById('paymentUploadBtn');
  const paymentInput = document.getElementById('paymentInput');
  const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
  const profilePhotoInput = document.getElementById('profilePhotoInput');
  const geocodeBtn = document.getElementById('geocodeBtn');
  const slugInput = document.getElementById('publicSlugInput');

  uploadBtn?.addEventListener('click', () => fileInput?.click());
  uploadBackBtn?.addEventListener('click', () => backFileInput?.click());
  cameraBtn?.addEventListener('click', () => cameraInput?.click());
  categorySelect?.addEventListener('change', handleDocumentTypeChange);
  idSubtypeSelect?.addEventListener('change', maybeUploadPendingIdDocument);
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
      handleFrontFileSelection(file);
    }
  });
}

function handleFileInputChange(e) {
  const file = e.target.files?.[0];
  if (file) {
    handleFrontFileSelection(file);
  }
  e.target.value = '';
}

function handleBackFileInputChange(e) {
  const file = e.target.files?.[0];
  if (file) {
    pendingBackFile = file;
    updateUploadBackFilename(file.name);
    maybeUploadPendingIdDocument();
  }
  e.target.value = '';
}

function handleFrontFileSelection(file) {
  updateUploadFilename(file.name);
  const category = document.getElementById('categorySelect')?.value;
  if (category === 'ID Bewijs') {
    pendingFrontFile = file;
    maybeUploadPendingIdDocument();
    return;
  }
  pendingFrontFile = null;
  pendingBackFile = null;
  updateUploadBackFilename('');
  handleFileUpload(file);
}

function showLoginModal() {
  document.getElementById('loginModal').classList.remove('hidden');
  document.getElementById('loginModal').classList.remove('modal-enter');
  document.getElementById('loginModal').classList.add('modal-active');
}

function hideLoginModal() {
  const modal = document.getElementById('loginModal');
  if (!modal) return;
  modal.classList.add('hidden');
  modal.classList.remove('modal-active');
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
      if (companyRes.status === 404) {
        showCreateCompanyForm();
        if (window.location.pathname !== '/profile') {
          showToast('LET OP: Geen company gevonden voor deze gebruiker.', 'error');
          window.location.href = '/profile';
        }
        return;
      }
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
    hideCreateCompanyForm();

    fetchDashboard();
    fetchDocuments();
    fetchNotifications();
    fetchPaymentProofPreview();
    fetchPaymentProofStatus();
    checkGeminiHealth();
  } catch (err) {
    console.error(err);
    handleLogout(); // Bij twijfel: uitloggen
  }
}

const companyProfileForm = document.getElementById('companyProfileForm');
if (companyProfileForm) {
companyProfileForm.addEventListener('submit', async (e) => {
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
    if (!currentCompany?.id) {
      showCreateCompanyForm();
      if (statusEl) statusEl.textContent = 'Maak eerst een bedrijf aan.';
      return;
    }
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
}

const digitalIdForm = document.getElementById('digitalIdForm');
if (digitalIdForm) {
digitalIdForm.addEventListener('submit', async (e) => {
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
}

const downloadProfileBtn = document.getElementById('downloadProfileBtn');
if (downloadProfileBtn) {
downloadProfileBtn.addEventListener('click', async () => {
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
}

const loginForm = document.getElementById('loginForm');
if (loginForm) {
loginForm.addEventListener('submit', async (e) => {
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
}

function handleLogout() {
  localStorage.removeItem('lce_token');
  location.reload();
}

// --- DASHBOARD DATA ---

async function fetchDashboard() {
  const hasScoreUi = Boolean(document.getElementById('scoreMessage'));
  const hasCategorySelect = Boolean(document.getElementById('categorySelect'));
  if (!hasScoreUi && !hasCategorySelect) return;
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
    if (hasScoreUi) {
      updateGauge(data.current_score);
    }

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

    if (hasScoreUi) {
      document.getElementById('statValid').textContent = counts.valid;
      document.getElementById('statReview').textContent = counts.review + counts.missing;
      document.getElementById('statProcessing').textContent = counts.processing;
      document.getElementById('statInvalid').textContent = counts.invalid;
    }

    // Update Message
    if (hasScoreUi) {
      const msgEl = document.getElementById('scoreMessage');
      if (data.current_score >= 91) msgEl.textContent = 'Uitstekend! Uw compliance is op orde.';
      else if (data.current_score >= 51) msgEl.textContent = 'Goed bezig, maar er ontbreken nog documenten.';
      else msgEl.textContent = 'Let op: Uw compliance score is kritiek laag.';
    }
  } catch (err) {
    console.error('Dashboard fetch error', err);
    if (hasScoreUi) {
      document.getElementById('scoreMessage').textContent = 'Kan dashboard niet laden.';
    }
  }
}

function updateGauge(score) {
  if (!document.getElementById('gaugeProgress')) return;
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

function formatDateTime(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  try {
    return new Intl.DateTimeFormat('nl-NL', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  } catch (e) {
    return date.toLocaleString();
  }
}

// --- DOCUMENTS LIST ---

async function fetchDocuments() {
  const tbody = document.getElementById('documentsTableBody');
  if (!tbody) return;
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
      const safeDate = escapeHtml(formatDateTime(doc.updated_at || '-'));
      const docId = doc.id || doc.uuid || Math.random().toString(36).slice(2);
      const aiReason = escapeHtml(doc.extracted_data?.ai_reason || '');
      const aiFix = escapeHtml(doc.extracted_data?.ai_fix || '');
      const aiFeedback = escapeHtml(doc.ai_feedback || 'Geen AI-advies beschikbaar.');
      const summary = doc.extracted_data?.ai_summary;
      const summaryHtml = renderSummary(summary, aiFeedback);
      const detectedType = escapeHtml(doc.detected_type || doc.category_selected || 'Onbekend');
      const expiry = escapeHtml(formatDateTime(doc.expiry_date || '-'));
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
                <p class="font-medium text-slate-700">OCR: ${ocr} · AI: ${formatAiConfidence(doc)}</p>
              </div>
            </div>
            <div class="mt-3 p-3 rounded-lg border border-slate-200 bg-white">
              <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">AI Advies</p>
              ${summaryHtml}
              ${aiReason ? `<p class=\"text-xs text-slate-500 mt-2\">Reden: ${aiReason}</p>` : ''}
              ${aiFix ? `<p class=\"text-xs text-slate-500 mt-1\">Fix: ${aiFix}</p>` : ''}
            </div>
            ${renderManualReviewNotice(doc)}
            <div class="mt-4 flex flex-wrap gap-3">
              <button class="text-xs font-semibold text-slate-700 hover:text-blue-700 border border-slate-200 px-3 py-1.5 rounded-lg" data-doc-reprocess="${docId}">
                Heranalyseer (Reprocess)
              </button>
              <button class="text-xs font-semibold text-red-600 hover:text-red-700 border border-red-200 px-3 py-1.5 rounded-lg" data-doc-delete="${docId}">
                Verwijder document
              </button>
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

async function handleFileUpload(file, options = {}) {
  if (!file) return;

  const category = document.getElementById('categorySelect')?.value;
  if (!category) {
    showToast('Kies eerst een documenttype.', 'error');
    setUploadError('Kies eerst een documenttype.');
    return;
  }

  setUploadBusy(true);
  setUploadProgress(5, 'Upload voorbereiden...');
  showToast(`Uploaden: ${file.name}...`, 'info');
  setUploadError('');

  const formData = new FormData();
  formData.append('file', file);
  formData.append('front_file', file);
  if (category) formData.append('category_selected', category);
  if (options.idSubtype) {
    formData.append('id_subtype', options.idSubtype);
  }
  if (options.backFile) {
    formData.append('back_file', options.backFile);
  }

  try {
    const ocrResultFront = await runBrowserOcr(file);
    const ocrResultBack = options.backFile ? await runBrowserOcr(options.backFile) : null;
    if (ocrResultFront || ocrResultBack) {
      setUploadProgress(45, 'OCR afgerond. Uploaden...');
    }
    if (ocrResultFront?.text) {
      formData.append('ocr_text', ocrResultFront.text);
      formData.append('ocr_text_front', ocrResultFront.text);
      if (ocrResultFront.confidence !== null && ocrResultFront.confidence !== undefined) {
        formData.append('ocr_confidence', String(ocrResultFront.confidence));
        formData.append('ocr_confidence_front', String(ocrResultFront.confidence));
      }
    }
    if (ocrResultBack?.text) {
      formData.append('ocr_text_back', ocrResultBack.text);
      if (ocrResultBack.confidence !== null && ocrResultBack.confidence !== undefined) {
        formData.append('ocr_confidence_back', String(ocrResultBack.confidence));
      }
    }

    setUploadProgress(60, 'Bestand uploaden...');
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
        setUploadError('AI-analyse vereist een Pro-plan. Upload je betaalbewijs om te upgraden.');
        return;
      }
      const msg = formatApiError(errData, 'Uploaden mislukt. Probeer opnieuw.');
      setUploadError(msg);
      throw new Error(msg);
    }

    const data = await res.json().catch(() => ({}));
    setUploadProgress(85, 'AI-analyse verwerken...');
    const status = data?.document?.status || data?.document?.ui_label || '';
    if (status && String(status).toUpperCase() !== 'PROCESSING') {
      showToast(`Bestand geüpload. AI-analyse klaar (${status}).`, 'success');
    } else {
      showToast('Bestand geüpload. AI-analyse gestart.', 'success');
    }
    setUploadError('');
    setUploadProgress(100, 'Klaar.');

    // Refresh data
    fetchDocuments();
    fetchDashboard();
  } catch (err) {
    showToast(`Fout: ${err.message}`, 'error');
    console.error(err);
  } finally {
    setUploadBusy(false);
  }
}

async function runBrowserOcr(file) {
  if (!file || !file.type?.startsWith('image/')) {
    return null;
  }
  setUploadProgress(25, 'OCR lezen...');
  if (!window.Tesseract) {
    setUploadProgress(35, 'OCR niet beschikbaar. Verder zonder OCR.');
    return null;
  }
  try {
    const result = await window.Tesseract.recognize(file, 'eng');
    const text = result?.data?.text?.trim() || '';
    const confidence = result?.data?.confidence ?? null;
    return { text, confidence };
  } catch (err) {
    setUploadProgress(35, 'OCR mislukt. Verder zonder OCR.');
    return null;
  }
}

function setUploadProgress(percent, label) {
  const wrap = document.getElementById('uploadProgress');
  const bar = document.getElementById('uploadProgressBar');
  const pct = document.getElementById('uploadProgressPct');
  const text = document.getElementById('uploadProgressLabel');
  if (!wrap || !bar || !pct || !text) return;
  wrap.classList.remove('hidden');
  const clamped = Math.max(0, Math.min(100, Number(percent) || 0));
  bar.style.width = `${clamped}%`;
  pct.textContent = `${clamped}%`;
  if (label) text.textContent = label;
  if (clamped >= 100) {
    setTimeout(() => wrap.classList.add('hidden'), 2000);
  }
}

function formatApiError(errData, fallback) {
  if (!errData) return fallback;
  const code = (errData.code || '').toString().toUpperCase();
  const message = (errData.message || '').toString();

  const codeMap = {
    PLAN_RESTRICTED: 'AI-analyse vereist een Pro-plan. Upload je betaalbewijs om te upgraden.',
    NOT_FOUND: 'Document niet gevonden.',
    INVALID_FILE: 'Het bestand is ongeldig. Probeer een andere upload.',
    LOW_OCR_CONFIDENCE: 'De foto is te donker of onleesbaar. Probeer een scherpere foto.',
    DUPLICATE_DOCUMENT: 'Dit document is al eerder geüpload.',
    VALIDATION_ERROR: 'Controleer het formulier. Er ontbreekt verplichte info.'
  };

  if (codeMap[code]) return codeMap[code];
  if (message.includes('Data truncated for column')) {
    return 'Interne fout bij het verwerken van dit document. Probeer opnieuw.';
  }
  if (message.includes('SQLSTATE')) {
    return 'Er ging iets mis op de server. Probeer het later opnieuw.';
  }
  return message || fallback;
}

function setUploadError(message) {
  const el = document.getElementById('uploadError');
  if (!el) return;
  if (!message) {
    el.textContent = '';
    el.classList.add('hidden');
    return;
  }
  el.textContent = message;
  el.classList.remove('hidden');
}

async function checkGeminiHealth() {
  const el = document.getElementById('geminiHealth');
  if (!el) return;
  try {
    const res = await fetch(`${API_BASE}/gemini/health`, {
      headers: { 'Authorization': `Bearer ${authToken}` }
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      el.textContent = 'AI-status: niet beschikbaar.';
      return;
    }
    const status = data?.result?.status || 'unknown';
    const message = data?.result?.message || '';
    if (status === 'ok') {
      el.textContent = `AI-status: verbonden. ${message}`;
    } else {
      el.textContent = `AI-status: fout. ${message}`;
    }
  } catch (err) {
    el.textContent = 'AI-status: fout bij verbinden.';
  }
}


function setUploadBusy(isBusy) {
  const uploadBtn = document.getElementById('uploadBtn');
  const uploadBackBtn = document.getElementById('uploadBackBtn');
  const cameraBtn = document.getElementById('cameraBtn');
  const idSubtypeSelect = document.getElementById('idSubtypeSelect');
  const spinner = document.getElementById('uploadSpinner');
  if (uploadBtn) {
    uploadBtn.disabled = isBusy;
    uploadBtn.classList.toggle('opacity-70', isBusy);
    uploadBtn.classList.toggle('cursor-not-allowed', isBusy);
  }
  if (cameraBtn) {
    cameraBtn.disabled = isBusy;
    cameraBtn.classList.toggle('opacity-70', isBusy);
    cameraBtn.classList.toggle('cursor-not-allowed', isBusy);
  }
  if (uploadBackBtn) {
    uploadBackBtn.disabled = isBusy;
    uploadBackBtn.classList.toggle('opacity-70', isBusy);
    uploadBackBtn.classList.toggle('cursor-not-allowed', isBusy);
  }
  if (idSubtypeSelect) {
    idSubtypeSelect.disabled = isBusy;
  }
  if (spinner) {
    spinner.classList.toggle('hidden', !isBusy);
    spinner.classList.toggle('inline-flex', isBusy);
  }
  if (!isBusy) {
    setUploadProgress(0, 'Verwerking starten...');
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
  const nameEl = document.getElementById('companyNameInput');
  const sectorEl = document.getElementById('companySectorInput');
  const expEl = document.getElementById('companyExperienceInput');
  const contactEl = document.getElementById('companyContactInput');
  if (nameEl) nameEl.value = company.company_name || '';
  if (sectorEl) sectorEl.value = company.sector || '';
  if (expEl) expEl.value = company.experience || '';
  if (contactEl) contactEl.value = company.contact?.email || '';
}

function populateDigitalIdForm(company) {
  const slugEl = document.getElementById('publicSlugInput');
  const displayEl = document.getElementById('displayNameInput');
  const addressEl = document.getElementById('addressInput');
  const latEl = document.getElementById('latInput');
  const lngEl = document.getElementById('lngInput');
  if (slugEl) slugEl.value = company.public_slug || '';
  if (displayEl) displayEl.value = company.display_name || '';
  if (addressEl) addressEl.value = company.address || '';
  if (latEl) latEl.value = company.lat || '';
  if (lngEl) lngEl.value = company.lng || '';

  const link = document.getElementById('publicProfileLink');
  if (link) {
    const slug = company.public_slug || '';
    link.href = slug ? `/p/${slug}` : '#';
    link.textContent = slug ? `Bekijk publieke link: /p/${slug}` : 'Stel je slug in om de link te zien';
  }

  const preview = document.getElementById('profilePhotoPreview');
  if (preview) {
    if (company.profile_photo_path) {
      loadProfilePhotoPreview();
    } else {
      preview.classList.add('hidden');
    }
  }
}

const companyCreateForm = document.getElementById('companyCreateForm');
if (companyCreateForm) {
  companyCreateForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('companyCreateStatus');
    const payload = {
      company_name: document.getElementById('createCompanyName').value.trim(),
      sector: document.getElementById('createCompanySector').value.trim(),
      experience: document.getElementById('createCompanyExperience').value.trim(),
      contact: {
        email: document.getElementById('createCompanyContact').value.trim(),
      },
    };
    try {
    const res = await fetch(`${API_BASE}/companies`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json',
      },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.message || 'Aanmaken mislukt');
      currentCompany = data.company;
      hideCreateCompanyForm();
      populateCompanyForm(currentCompany);
      showToast('Bedrijf aangemaakt', 'success');
      fetchDashboard();
      fetchDocuments();
    } catch (err) {
      if (statusEl) statusEl.textContent = err.message || 'Aanmaken mislukt.';
      showToast(err.message || 'Aanmaken mislukt', 'error');
    }
  });
}

function showCreateCompanyForm() {
  const panel = document.getElementById('companyCreatePanel');
  const profilePanel = document.getElementById('companyProfilePanel');
  if (panel) panel.classList.remove('hidden');
  if (profilePanel) profilePanel.classList.add('hidden');
}

function hideCreateCompanyForm() {
  const panel = document.getElementById('companyCreatePanel');
  const profilePanel = document.getElementById('companyProfilePanel');
  if (panel) panel.classList.add('hidden');
  if (profilePanel) profilePanel.classList.remove('hidden');
}

function bindDocumentRowToggles() {
  const tbody = document.getElementById('documentsTableBody');
  if (!tbody || tbody.dataset.togglesBound === 'true') return;
  tbody.dataset.togglesBound = 'true';

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-doc-action]');
    const delBtn = e.target.closest('[data-doc-delete]');
    const reprocessBtn = e.target.closest('[data-doc-reprocess]');
    if (!btn && !delBtn && !reprocessBtn) return;

    if (delBtn) {
      const docId = delBtn.getAttribute('data-doc-delete');
      if (!docId) return;
      if (!confirm('Weet je zeker dat je dit document wil verwijderen?')) return;
      deleteDocument(docId);
      return;
    }

    if (reprocessBtn) {
      const docId = reprocessBtn.getAttribute('data-doc-reprocess');
      if (!docId) return;
      reprocessDocument(docId);
      return;
    }

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

async function deleteDocument(docId) {
  try {
    const res = await fetch(`${API_BASE}/documents/${docId}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${authToken}` }
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      showToast(data.message || 'Verwijderen mislukt.', 'error');
      return;
    }
    showToast('Document verwijderd.', 'success');
    fetchDocuments();
    fetchDashboard();
  } catch (err) {
    showToast('Netwerkfout bij verwijderen.', 'error');
  }
}

async function reprocessDocument(docId) {
  try {
    showToast('Document wordt opnieuw geanalyseerd...', 'info');
    const res = await fetch(`${API_BASE}/documents/${docId}/reprocess`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${authToken}` }
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      showToast(data.message || 'Heranalyse mislukt.', 'error');
      return;
    }
    showToast('Heranalyse gestart.', 'success');
    fetchDocuments();
    fetchDashboard();
  } catch (err) {
    showToast('Netwerkfout bij heranalyse.', 'error');
  }
}

function updateCategoryOptions(requiredDocs) {
  const select = document.getElementById('categorySelect');
  if (!select) return;
  const previous = select.value;
  const options = Array.isArray(requiredDocs) ? requiredDocs : [];
  const unique = [...new Set(options.map((item) => item.type).filter(Boolean))];

  select.innerHTML = '<option value="">Kies documenttype</option>';
  unique.forEach((type) => {
    const opt = document.createElement('option');
    opt.value = type;
    opt.textContent = type;
    select.appendChild(opt);
  });
  if (previous && unique.includes(previous)) {
    select.value = previous;
  }
  handleDocumentTypeChange();
}

function updateUploadFilename(name) {
  const el = document.getElementById('uploadFilename');
  if (el) el.textContent = name ? `Geselecteerd: ${name}` : '';
}

function updateUploadBackFilename(name) {
  const el = document.getElementById('uploadBackFilename');
  if (!el) return;
  if (!name) {
    el.textContent = '';
    el.classList.add('hidden');
    return;
  }
  el.textContent = `Achterzijde: ${name}`;
  el.classList.remove('hidden');
}

function handleDocumentTypeChange() {
  const category = document.getElementById('categorySelect')?.value;
  const subtype = document.getElementById('idSubtypeSelect');
  const backBtn = document.getElementById('uploadBackBtn');
  const isId = category === 'ID Bewijs';

  if (isId) {
    subtype?.classList.remove('hidden');
    backBtn?.classList.remove('hidden');
    backBtn?.classList.add('inline-flex');
  } else {
    subtype && (subtype.value = '');
    subtype?.classList.add('hidden');
    backBtn?.classList.add('hidden');
    backBtn?.classList.remove('inline-flex');
    pendingFrontFile = null;
    pendingBackFile = null;
    updateUploadBackFilename('');
  }
}

function maybeUploadPendingIdDocument() {
  const category = document.getElementById('categorySelect')?.value;
  if (category !== 'ID Bewijs') return;
  const subtype = document.getElementById('idSubtypeSelect')?.value;
  if (!pendingFrontFile) return;
  if (!subtype) {
    showToast('Kies eerst een ID subtype.', 'error');
    return;
  }
  const needsBack = subtype === 'id_kaart' || subtype === 'rijbewijs';
  if (needsBack && !pendingBackFile) {
    showToast('Voor dit subtype is een achterzijde verplicht.', 'info');
    return;
  }

  const front = pendingFrontFile;
  const back = pendingBackFile;
  pendingFrontFile = null;
  pendingBackFile = null;
  handleFileUpload(front, { backFile: back, idSubtype: subtype });
}

function formatAiConfidence(doc) {
  if (doc?.extracted_data?.ai_summary) {
    return 'n.v.t.';
  }
  if (doc?.ai_confidence === null || doc?.ai_confidence === undefined) {
    return '-';
  }
  const value = Number(doc.ai_confidence);
  if (Number.isNaN(value)) return '-';
  const percent = value <= 1 ? Math.round(value * 100) : Math.round(value);
  return `${percent}%`;
}

function renderSummary(summary, fallbackText) {
  if (!summary || typeof summary !== 'object') {
    return `<p class="text-slate-700">${fallbackText}</p>`;
  }
  const summaryText = summary.summary ? `<p class="text-slate-700">${escapeHtml(summary.summary)}</p>` : `<p class="text-slate-700">${fallbackText}</p>`;
  const findings = Array.isArray(summary.findings) ? summary.findings : [];
  const improvements = Array.isArray(summary.improvements) ? summary.improvements : [];
  const missing = Array.isArray(summary.missing_items) ? summary.missing_items : [];
  const list = (items) => items.length ? `<ul class="list-disc pl-4">${items.map((i) => `<li>${escapeHtml(i)}</li>`).join('')}</ul>` : '<p class="text-slate-500">—</p>';
  return `
    <div class="space-y-3 text-sm">
      <div>${summaryText}</div>
      <div>
        <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Bevindingen</p>
        ${list(findings)}
      </div>
      <div>
        <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Verbeteringen</p>
        ${list(improvements)}
      </div>
      <div>
        <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Ontbrekend</p>
        ${list(missing)}
      </div>
    </div>
  `;
}

function labelField(field) {
  const map = {
    bedrijfsnaam: 'Bedrijfsnaam',
    kvk_nummer: 'KKF/KKV nummer',
    uitgifte_datum: 'Uitgiftedatum',
    issue_date: 'Uitgiftedatum',
    expiry_date: 'Vervaldatum',
    document_type: 'Documenttype',
    vergunning_nummer: 'Vergunningnummer',
    vergunning_type: 'Vergunning type',
    belasting_nummer: 'Belastingnummer',
    id_type: 'ID type',
    id_nummer: 'ID nummer',
    paspoort_nummer: 'Paspoortnummer',
    rijbewijs_nummer: 'Rijbewijsnummer',
    nationaliteit: 'Nationaliteit',
    geboortedatum: 'Geboortedatum',
  };
  if (map[field]) return map[field];
  return String(field)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function renderManualReviewNotice(doc) {
  const status = String(doc?.status || '').toUpperCase();
  if (status !== 'MANUAL_REVIEW') return '';
  return `
    <div class="mt-3 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
      <strong>Handmatige controle nodig.</strong>
      <div>${escapeHtml(doc.ai_feedback || 'De AI kon dit document niet betrouwbaar valideren.')}</div>
    </div>
  `;
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
    await loadProfilePhotoPreview();
  } catch (err) {
    showToast(err.message || 'Upload mislukt', 'error');
    if (statusEl) statusEl.textContent = 'Upload mislukt.';
  }
  e.target.value = '';
}

async function loadProfilePhotoPreview() {
  const preview = document.getElementById('profilePhotoPreview');
  if (!preview) return;
  try {
    const res = await fetch(`${API_BASE}/companies/me/profile-photo`, {
      headers: { 'Authorization': `Bearer ${authToken}` },
    });
    if (!res.ok) throw new Error('Foto niet gevonden');
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    preview.src = url;
    preview.classList.remove('hidden');
  } catch (err) {
    preview.classList.add('hidden');
  }
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
    if (!res.ok) {
      if (data.code === 'GEOCODE_FAILED') {
        await handleClientSideGeocode(address, statusEl);
        return;
      }
      throw new Error(data.message || 'Geocode mislukt');
    }
    document.getElementById('latInput').value = data.lat || '';
    document.getElementById('lngInput').value = data.lng || '';
    if (statusEl) statusEl.textContent = 'Locatie ingevuld.';
  } catch (err) {
    if (statusEl) statusEl.textContent = err.message || 'Geocode mislukt.';
  }
}

async function handleClientSideGeocode(address, statusEl) {
  try {
    const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=1`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('Geocode mislukt (client)');
    const data = await res.json();
    const item = Array.isArray(data) && data.length ? data[0] : null;
    if (!item) throw new Error('Geen locatie gevonden');
    document.getElementById('latInput').value = item.lat || '';
    document.getElementById('lngInput').value = item.lon || '';
    if (statusEl) statusEl.textContent = 'Locatie ingevuld (client).';
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
    await fetchPaymentProofPreview(true);
  } catch (err) {
    showToast(err.message || 'Upload mislukt', 'error');
    if (statusEl) statusEl.textContent = 'Upload mislukt. Probeer opnieuw.';
  }
  e.target.value = '';
}

async function fetchPaymentProofPreview(force = false) {
  const preview = document.getElementById('paymentProofPreview');
  const link = document.getElementById('paymentProofLink');
  if (!preview || !link) return;
  try {
    const res = await fetch(`${API_BASE}/payment-proofs/latest`, {
      headers: { 'Authorization': `Bearer ${authToken}` },
    });
    if (!res.ok) return;
    const data = await res.json();
    if (!data?.payment_proof) return;

    const fileRes = await fetch(`${API_BASE}/payment-proofs/latest/file${force ? `?ts=${Date.now()}` : ''}`, {
      headers: { 'Authorization': `Bearer ${authToken}` },
    });
    if (!fileRes.ok) {
      link.classList.add('hidden');
      preview.classList.add('hidden');
      return;
    }
    const blob = await fileRes.blob();
    const url = URL.createObjectURL(blob);
    if (blob.type.startsWith('image/')) {
      preview.src = url;
      preview.classList.remove('hidden');
      link.classList.add('hidden');
    } else {
      link.href = url;
      link.textContent = 'Open betaalbewijs';
      link.classList.remove('hidden');
      preview.classList.add('hidden');
    }
  } catch (err) {
    // best-effort
  }
}

async function fetchPaymentProofStatus() {
  try {
    const res = await fetch(`${API_BASE}/payment-proofs/latest`, {
      headers: { 'Authorization': `Bearer ${authToken}` },
    });
    if (!res.ok) return;
    const data = await res.json();
    if (!data?.payment_proof) return;
    if (data.payment_proof.status === 'PENDING') {
      updatePlanBadges({ plan: currentUser?.plan || 'FREE', plan_status: 'PENDING_PAYMENT' });
      const statusEl = document.getElementById('paymentStatus');
      if (statusEl) statusEl.textContent = 'Betaalbewijs ontvangen. Status: Pending Payment.';
    }
  } catch (err) {
    // best-effort
  }
}

async function apiFetch(path, options = {}) {
  if (!authToken) {
    throw new Error('Niet ingelogd');
  }
  const headers = options.headers ? { ...options.headers } : {};
  headers['Authorization'] = `Bearer ${authToken}`;
  if (!(options.body instanceof FormData) && options.method && options.method !== 'GET') {
    headers['Content-Type'] = headers['Content-Type'] || 'application/json';
  }
  return fetch(`${API_BASE}${path}`, { ...options, headers });
}

function initUserTendersPage() {
  const listEl = document.getElementById('tendersList');
  const myListEl = document.getElementById('myTendersList');
  if (!listEl || !myListEl) return;

  const refreshBtn = document.getElementById('refreshTendersBtn');
  const refreshMyBtn = document.getElementById('refreshMyTendersBtn');
  const form = document.getElementById('tenderForm');
  const statusEl = document.getElementById('tenderFormStatus');
  const submitBtn = document.getElementById('tenderSubmitBtn');

  refreshBtn?.addEventListener('click', loadApprovedTenders);
  refreshMyBtn?.addEventListener('click', loadMyTenders);

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!submitBtn) return;
    submitBtn.disabled = true;
    if (statusEl) statusEl.textContent = 'Bezig met insturen...';

    const title = document.getElementById('tenderTitle')?.value?.trim();
    const client = document.getElementById('tenderClient')?.value?.trim();
    const date = document.getElementById('tenderDate')?.value || null;
    const isDirectWork = document.getElementById('tenderDirectWork')?.value === '1';
    const detailsUrl = document.getElementById('tenderUrl')?.value?.trim();
    const description = document.getElementById('tenderDescription')?.value?.trim();
    const attachmentsRaw = document.getElementById('tenderAttachments')?.value || '';
    const attachments = attachmentsRaw
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean);

    if (!title) {
      showToast('Titel is verplicht.', 'error');
      if (statusEl) statusEl.textContent = 'Titel is verplicht.';
      submitBtn.disabled = false;
      return;
    }

    try {
      const formData = new FormData();
      formData.append('title', title);
      if (client) formData.append('client', client);
      if (date) formData.append('date', date);
      if (detailsUrl) formData.append('details_url', detailsUrl);
      if (description) formData.append('description', description);
      formData.append('is_direct_work', isDirectWork ? '1' : '0');
      if (attachments.length) {
        formData.append('attachments_urls', attachments.join('\n'));
      }
      const attachmentFiles = document.getElementById('tenderAttachmentFiles')?.files || [];
      Array.from(attachmentFiles).forEach((file) => {
        formData.append('attachments_files[]', file);
      });

      const res = await apiFetch('/tenders', {
        method: 'POST',
        body: formData,
      });
      const data = await res.json();
      if (!res.ok) {
        const msg = formatApiError(data, 'Insturen mislukt. Probeer opnieuw.');
        showToast(msg, 'error');
        if (statusEl) statusEl.textContent = msg;
        submitBtn.disabled = false;
        return;
      }

      showToast('Aanbesteding ingestuurd. Wacht op goedkeuring.', 'success');
      if (statusEl) statusEl.textContent = 'Inzending ontvangen. Status: in afwachting.';
      form.reset();
      await loadMyTenders();
    } catch (err) {
      const msg = err?.message || 'Insturen mislukt. Probeer opnieuw.';
      showToast(msg, 'error');
      if (statusEl) statusEl.textContent = msg;
    } finally {
      submitBtn.disabled = false;
    }
  });

  loadApprovedTenders();
  loadMyTenders();

  async function loadApprovedTenders() {
    listEl.innerHTML = '<p class="text-sm text-slate-500">Aanbestedingen laden...</p>';
    try {
      const res = await apiFetch('/tenders');
      const data = await res.json();
      if (!res.ok) {
        listEl.innerHTML = `<p class="text-sm text-red-500">${data.message || 'Aanbestedingen ophalen mislukt.'}</p>`;
        return;
      }
      renderTenderList(listEl, data.tenders || []);
    } catch (err) {
      listEl.innerHTML = `<p class="text-sm text-red-500">${err?.message || 'Aanbestedingen ophalen mislukt.'}</p>`;
    }
  }

  async function loadMyTenders() {
    myListEl.innerHTML = '<p class="text-sm text-slate-500">Inzendingen laden...</p>';
    try {
      const res = await apiFetch('/tenders/mine');
      const data = await res.json();
      if (!res.ok) {
        myListEl.innerHTML = `<p class="text-sm text-red-500">${data.message || 'Inzendingen ophalen mislukt.'}</p>`;
        return;
      }
      renderMyTenderList(myListEl, data.tenders || []);
    } catch (err) {
      myListEl.innerHTML = `<p class="text-sm text-red-500">${err?.message || 'Inzendingen ophalen mislukt.'}</p>`;
    }
  }

  function renderTenderList(target, items) {
    if (!items.length) {
      target.innerHTML = '<p class="text-sm text-slate-500">Geen aanbestedingen gevonden.</p>';
      return;
    }
    const html = items.map((tender) => {
      const tag = tender.is_direct_work ? '<span class="px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-700">Direct werk</span>' : '';
      const date = tender.date ? new Date(tender.date).toLocaleDateString('nl-NL') : 'Onbekend';
      const url = tender.details_url ? `<a href="${tender.details_url}" target="_blank" rel="noopener" class="text-blue-600 text-sm">Details bekijken</a>` : '<span class="text-slate-400 text-sm">Details afgeschermd</span>';
      const description = tender.description || 'Geen omschrijving beschikbaar.';
      const attachmentCount = Array.isArray(tender.attachments) ? tender.attachments.length : 0;
      return `
        <div class="border border-slate-200 rounded-xl p-4 mb-3">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h4 class="font-semibold text-slate-800">${tender.title || tender.project || 'Aanbesteding'}</h4>
              <p class="text-xs text-slate-500 mt-1">Opdrachtgever: ${tender.client || 'Onbekend'} • Datum: ${date}</p>
            </div>
            ${tag}
          </div>
          <p class="text-sm text-slate-600 mt-2">${description}</p>
          <p class="text-xs text-slate-500 mt-2">Bijlagen: ${attachmentCount}</p>
          <div class="mt-3">${url}</div>
        </div>
      `;
    }).join('');
    target.innerHTML = html;
  }

  function renderMyTenderList(target, items) {
    if (!items.length) {
      target.innerHTML = '<p class="text-sm text-slate-500">Je hebt nog geen aanbestedingen ingestuurd.</p>';
      return;
    }
    const statusMap = {
      PENDING: { label: 'In afwachting', cls: 'bg-yellow-100 text-yellow-800' },
      APPROVED: { label: 'Goedgekeurd', cls: 'bg-green-100 text-green-700' },
      REJECTED: { label: 'Afgewezen', cls: 'bg-red-100 text-red-700' },
    };

    const html = items.map((tender) => {
      const status = statusMap[tender.status] || { label: tender.status || 'Onbekend', cls: 'bg-slate-100 text-slate-600' };
      const submitted = tender.submitted_at ? new Date(tender.submitted_at).toLocaleString('nl-NL') : 'Onbekend';
      const attachmentCount = Array.isArray(tender.attachments) ? tender.attachments.length : 0;
      return `
        <div class="border border-slate-200 rounded-xl p-4 mb-3">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
              <h4 class="font-semibold text-slate-800">${tender.title || tender.project || 'Aanbesteding'}</h4>
              <p class="text-xs text-slate-500 mt-1">Ingestuurd op ${submitted}</p>
            </div>
            <span class="px-2 py-0.5 text-xs rounded-full ${status.cls}">${status.label}</span>
          </div>
          <p class="text-sm text-slate-600 mt-2">${tender.description || 'Geen omschrijving beschikbaar.'}</p>
          <p class="text-xs text-slate-500 mt-2">Bijlagen: ${attachmentCount}</p>
        </div>
      `;
    }).join('');
    target.innerHTML = html;
  }
}
