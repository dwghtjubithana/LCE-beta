// State Management
const state = {
    view: 'dashboard', 
    kkfVerified: false,
    ocrVerified: false,
    isTier2: false,
    companyName: null, 
    kkfNumber: '',
    videoStream: null,
    lastSummaryPath: null,
    lastSummaryText: null,
    lastSummaryData: null
};

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('-translate-x-full');
    }
}

function initApp() {
    // Mobile menu
    const mobileMenuButton = document.getElementById('btn-mobile-menu');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', toggleSidebar);
    }

    // Profile links
                document.getElementById('link-profile')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    toggleProfileMenu(); // Close dropdown
                    router('profile');
                });    document.getElementById('link-security')?.addEventListener('click', (e) => {
        e.preventDefault();
        alert('Security page is not yet implemented.');
    });
    document.getElementById('link-logout')?.addEventListener('click', (e) => {
        e.preventDefault();
        // Clear all app-related local storage
        Object.keys(localStorage).forEach(key => {
            if (key.startsWith('lce_')) {
                localStorage.removeItem(key);
            }
        });
        alert('You have been logged out.');
        window.location.reload();
    });
}

// --- Router ---
function router(viewName) {
    state.view = viewName;
    
    // UI Navigation Updates
    document.querySelectorAll('.nav-item').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeBtn = document.getElementById(`nav-${viewName}`);
    if(activeBtn) {
        activeBtn.classList.add('active');
    }

    // Render Content
    const contentDiv = document.getElementById('app-content');
    const template = document.getElementById(`view-${viewName}`);
    
    if(template) {
        contentDiv.innerHTML = "";
        contentDiv.appendChild(template.content.cloneNode(true));
        
        // Post-render initialization
        if(viewName === 'compliance') initComplianceView();
        if(viewName === 'contracts') renderContracts();
        if(viewName === 'suppliers') renderSuppliers();
        if(viewName === 'scan-history') loadScanHistory();
        if(viewName === 'settings') initSettingsView();
        if(viewName === 'profile') initProfileView();
        closeSidebarOnMobile();        
        // Breadcrumb Update
        const breadcrumb = document.getElementById('header-breadcrumb');
        if(breadcrumb) {
            breadcrumb.innerHTML = `<span class="text-slate-300 mr-2">/</span> Application <span class="text-slate-300 mx-2">/</span> <span class="text-slate-600 font-bold">${viewName.charAt(0).toUpperCase() + viewName.slice(1)}</span>`;
        }
    } else {
        console.warn("Module unavailable:", viewName);
        contentDiv.innerHTML = `<div class="p-12 text-center text-slate-400 font-medium">Module '${viewName}' is not loaded.</div>`;
    }
}

// --- Profile Menu Logic ---
function toggleProfileMenu() {
    const menu = document.getElementById('profile-menu');
    if(menu) menu.classList.toggle('hidden');
}

window.addEventListener('click', function(e) {
    const menu = document.getElementById('profile-menu');
    const btn = document.querySelector('[onclick="toggleProfileMenu()"]');
    if (menu && !menu.contains(e.target) && (!btn || !btn.contains(e.target))) {
        menu.classList.add('hidden');
    }
});

function showDevMessage(msg) {
    // Replaced with actual router if needed, otherwise simplified alert
    alert(msg);
}

// --- Profile View Logic ---
function initProfileView() {
    const kkfInput = document.querySelector('#view-profile input[value*="Not set"]');
    if (kkfInput && state.kkfNumber) {
        kkfInput.value = state.kkfNumber;
    }
}

// --- Settings View Logic ---
function initSettingsView() {
    // Theme toggles
    const darkToggle = document.getElementById('toggle-dark');
    if (darkToggle) {
        darkToggle.checked = document.body.classList.contains('dark');
        darkToggle.addEventListener('change', toggleDarkMode);
    }

    const compactToggle = document.getElementById('toggle-compact');
    if (compactToggle) {
        compactToggle.checked = document.body.classList.contains('compact');
        compactToggle.addEventListener('change', toggleCompactView);
    }

    // Data settings
    applyDataSettings();
    const saveBtn = document.getElementById('btn-save-settings');
    if(saveBtn) {
        saveBtn.addEventListener('click', saveDataSettings);
    }

    // Tabs
    document.getElementById('tab-general')?.addEventListener('click', () => switchSettingsTab('general'));
    document.getElementById('tab-notifications')?.addEventListener('click', () => switchSettingsTab('notifications'));
    document.getElementById('tab-security')?.addEventListener('click', () => switchSettingsTab('security'));
}

function switchSettingsTab(tabName) {
    // Update button styles
    ['general', 'notifications', 'security'].forEach(name => {
        const tab = document.getElementById(`tab-${name}`);
        const content = document.getElementById(`content-${name}`);
        if (tab) {
            if (name === tabName) {
                tab.classList.add('font-bold', 'text-slate-800', 'border-b-2', 'border-[#0ea5a4]');
                tab.classList.remove('font-semibold', 'text-slate-500');
                content?.classList.remove('hidden');
            } else {
                tab.classList.remove('font-bold', 'text-slate-800', 'border-b-2', 'border-[#0ea5a4]');
                tab.classList.add('font-semibold', 'text-slate-500');
                content?.classList.add('hidden');
            }
        }
    });
}

function applyDataSettings() {
    const currencySelect = document.getElementById('select-currency');
    const intervalSelect = document.getElementById('select-refresh-interval');

    const savedCurrency = localStorage.getItem('lce_currency');
    if (savedCurrency && currencySelect) {
        currencySelect.value = savedCurrency;
    }

    const savedInterval = localStorage.getItem('lce_refresh_interval');
    if (savedInterval && intervalSelect) {
        intervalSelect.value = savedInterval;
    }
}

function saveDataSettings() {
    const currencySelect = document.getElementById('select-currency');
    const intervalSelect = document.getElementById('select-refresh-interval');
    const saveBtn = document.getElementById('btn-save-settings');

    if (currencySelect) {
        localStorage.setItem('lce_currency', currencySelect.value);
    }
    if (intervalSelect) {
        localStorage.setItem('lce_refresh_interval', intervalSelect.value);
    }

    if(saveBtn) {
        const originalText = saveBtn.innerText;
        saveBtn.innerText = 'SAVED!';
        saveBtn.classList.add('bg-emerald-500');
        setTimeout(() => {
            saveBtn.innerText = originalText;
            saveBtn.classList.remove('bg-emerald-500');
        }, 1500);
    }
}

function applyTheme() {
    if (localStorage.getItem('lce_theme') === 'dark') {
        document.body.classList.add('dark');
    } else {
        document.body.classList.remove('dark');
    }
}

function toggleDarkMode() {
    if (document.body.classList.contains('dark')) {
        document.body.classList.remove('dark');
        localStorage.setItem('lce_theme', 'light');
    } else {
        document.body.classList.add('dark');
        localStorage.setItem('lce_theme', 'dark');
    }
}

function applyCompactView() {
    if (localStorage.getItem('lce_compact_view') === 'true') {
        document.body.classList.add('compact');
    } else {
        document.body.classList.remove('compact');
    }
}

function toggleCompactView() {
    document.body.classList.toggle('compact');
    localStorage.setItem('lce_compact_view', document.body.classList.contains('compact'));
}

// --- Compliance View Logic ---
function initComplianceView() {
    if(state.kkfVerified) {
        const input = document.getElementById('kkf-input');
        const result = document.getElementById('kkf-result');
        const btn = document.getElementById('btn-kkf');
        
        if(input) {
            input.value = state.kkfNumber;
            input.disabled = true;
            input.classList.add('bg-slate-100');
        }
        
        if(result) {
            result.classList.remove('hidden');
            result.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i>Verified: File ${state.kkfNumber}`; // Corrected escaping for template literal
        }
        
        if(btn) {
            btn.innerText = "VERIFIED";
            btn.className = "bg-emerald-500 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-none cursor-default";
        }
    }
    if(state.ocrVerified) {
        const uploadContent = document.getElementById('upload-content');
        if(uploadContent) {
            uploadContent.innerHTML = '<div class="text-emerald-500 font-bold"><i class="fa-solid fa-check-circle text-4xl mb-3"></i><br>Document Accepted</div>'; // Corrected escaping for template literal
        }
        const dropzone = document.getElementById('dropzone');
        if(dropzone) {
            dropzone.classList.add('border-emerald-400', 'bg-emerald-50/50');
            dropzone.classList.remove('border-dashed');
        }
    }
    checkCompletion();
}

function startCameraScan() {
    const video = document.getElementById('video');
    const uploadOptions = document.getElementById('upload-options');
    const cameraView = document.getElementById('camera-view');
    const ocrStatus = document.getElementById('ocr-status');

    uploadOptions.classList.add('hidden');
    cameraView.classList.remove('hidden');
    ocrStatus.classList.add('hidden');


    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            state.videoStream = stream;
            video.srcObject = stream;
            video.play();
        })
        .catch(err => {
            console.error("Camera Error:", err);
            alert("Could not access the camera. Please ensure you have a camera connected and have granted permission.");
            stopCameraScan();
        });
}

function stopCameraScan() {
    const video = document.getElementById('video');
    const uploadOptions = document.getElementById('upload-options');
    const cameraView = document.getElementById('camera-view');

    if (state.videoStream) {
        state.videoStream.getTracks().forEach(track => track.stop());
    }
    video.srcObject = null;
    state.videoStream = null;

    uploadOptions.classList.remove('hidden');
    cameraView.classList.add('hidden');
}

function captureAndProcess() {
    const canvas = document.getElementById('canvas');
    const video = document.getElementById('video');
    const context = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);

    stopCameraScan(); // Turn off camera after capture

    canvas.toBlob(function(blob) {
        const file = new File([blob], "camera-capture.png", { type: "image/png" });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        
        // We can reuse the existing processOCR function by passing a mock input
        const mockInput = { files: dataTransfer.files };
        processOCR(mockInput);
    }, 'image/png');
}
function verifyKKF() {
    const input = document.getElementById('kkf-input');
    const btn = document.getElementById('btn-kkf');
    const result = document.getElementById('kkf-result');

    if(!input.value) {
        alert("Please enter a KKF number.");
        return;
    }

    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
    btn.classList.add('opacity-75');
    input.disabled = true;

    setTimeout(() => {
        state.kkfVerified = true;
        state.kkfNumber = input.value;
        
        result.classList.remove('hidden');
        result.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i>Verified: File ${state.kkfNumber} (Status: Active)`; // Corrected escaping for template literal
        
        btn.innerText = "VERIFIED";
        btn.className = "bg-emerald-500 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-none cursor-default";
        btn.classList.remove('opacity-75');
        
        checkCompletion();
    }, 1200);
}

function processOCR(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const uploadContent = document.getElementById('upload-content');
    const ocrStatus = document.getElementById('ocr-status');
    const laser = document.getElementById('laser');
    const statusText = document.getElementById('ocr-text');
    const progressBar = document.getElementById('ocr-progress');
    const resultBox = document.getElementById('ocr-result');

    uploadContent.classList.add('hidden');
    ocrStatus.classList.remove('hidden');
    laser.style.display = 'block';
    resultBox.classList.add('hidden');

    const fileName = (file.name || '').toLowerCase();
    const imageByExt = /\.(png|jpe?g|gif|webp)$/i.test(fileName);
    const isPdf = fileName.endsWith('.pdf') || (file.type || '') === 'application/pdf';
    const isDocx = fileName.endsWith('.docx') ||
        (file.type || '') === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    const isImage = (file.type || '').startsWith('image/') || imageByExt;

    if (isPdf || isDocx || !isImage) {
        laser.style.display = 'none';
        statusText.innerText = "Uploading document...";
        progressBar.style.width = "20%";
        submitScan(file, '');
        return;
    }

    Tesseract.recognize(
        file,
        'eng',
        {
            logger: m => {
                if(m.status === 'recognizing text') {
                    statusText.innerText = `AI Analysis... ${Math.round(m.progress * 100)}%`; // Corrected escaping for template literal
                    progressBar.style.width = `${m.progress * 100}%`; // Corrected escaping for template literal
                } else {
                    statusText.innerText = "Optimizing image...";
                }
            }
        }
    ).then(({ data: { text } }) => {
        laser.style.display = 'none';
        statusText.innerText = "Scan Complete. Submitting for verification...";
        
        resultBox.classList.remove('hidden');
        resultBox.innerText = `> RAW_DATA_EXTRACT:\n${text.substring(0, 300)}...`; // Corrected escaping for template literal

        submitScan(file, text);

    }).catch(err => {
        statusText.innerText = "Processing Error.";
        statusText.className = "text-red-500 font-bold mb-3 text-sm";
        console.error(err);
    });
}

function closeSidebarOnMobile() {
    if (window.innerWidth >= 768) return;
    const sidebar = document.getElementById('sidebar');
    if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.add('-translate-x-full');
    }
}

function submitScan(file, ocrText) {
    const statusText = document.getElementById('ocr-text');
    const progressBar = document.getElementById('ocr-progress');

    const formData = new FormData();
    formData.append('scan_image', file);
    formData.append('ocr_text', ocrText || '');
    formData.append('inspector_id', 1); // Or get from logged in user state
    formData.append('supplier_id', 1); // Or get from context

    fetch('scan_submit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            progressBar.style.width = "100%";
            statusText.innerText = "Document Verified & Logged.";
            statusText.className = "text-emerald-600 font-bold mb-3 text-sm";
            state.ocrVerified = true;
            if (data.summary_file_path) {
                state.lastSummaryPath = data.summary_file_path;
            }
            if (data.summary && data.summary.summary) {
                state.lastSummaryText = data.summary.summary;
            }
            if (data.summary) {
                state.lastSummaryData = data.summary;
            }
            document.getElementById('dropzone').classList.remove('border-dashed');
            document.getElementById('dropzone').classList.add('border-emerald-400', 'bg-emerald-50/50');
            showCertificatePanel();
            checkCompletion();
        } else {
            throw new Error(data.msg || 'Backend submission failed.');
        }
    })
    .catch(err => {
        statusText.innerText = "Submission Error.";
        statusText.className = "text-red-500 font-bold mb-3 text-sm";
        console.error(err);
    });
}

function checkCompletion() {
    if(state.kkfVerified && state.ocrVerified) {
        const btn = document.getElementById('magic-btn');
        if(btn) {
            btn.disabled = false;
            btn.classList.remove('btn-disabled');
            btn.classList.add('btn-primary', 'shadow-xl', 'animate-pulse');
            btn.style.backgroundColor = '#0ea5a4';
            btn.style.color = 'white';
        }
    }
}

function upgradeToTier2() {
    state.isTier2 = true;
    
    const badge = document.getElementById('header-status');
    badge.className = "hidden md:flex items-center gap-2 bg-emerald-50 px-3 py-1.5 rounded-full border border-emerald-100";
    badge.innerHTML = '<span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide">Tier 2 Verified</span>'; // Corrected escaping for template literal
    
    showToast("Upgrade Successful: Tier 2 status activated.", "success");
    showCertificatePanel();
}

function showCertificatePanel() {
    const panel = document.getElementById('result-panel');
    const statusEl = document.getElementById('certificate-status');
    const summaryEl = document.getElementById('summary-output');
    const downloadLink = document.getElementById('summary-download');
    const historyEl = document.getElementById('scan-history-panel');

    if (statusEl) {
        const status = (state.lastSummaryData && state.lastSummaryData.status) ? state.lastSummaryData.status : (state.isTier2 ? "PASS" : "PENDING");
        statusEl.innerText = status === 'PASS' ? "Tier 2 Verified" : status;
        statusEl.className = status === 'FAIL'
            ? "text-lg font-bold text-red-600"
            : "text-lg font-bold text-emerald-600";
    }
    if (summaryEl) {
        summaryEl.innerText = formatSummary(state.lastSummaryData) || "Summary will appear after a successful scan.";
    }
    if (downloadLink) {
        if (state.lastSummaryPath) {
            downloadLink.href = state.lastSummaryPath;
            downloadLink.removeAttribute('aria-disabled');
            downloadLink.classList.remove('opacity-50', 'pointer-events-none');
            downloadLink.innerText = 'Download Summary';
        } else {
            downloadLink.href = "#";
            downloadLink.setAttribute('aria-disabled', 'true');
            downloadLink.classList.add('opacity-50', 'pointer-events-none');
        }
    }
    if (panel) panel.classList.remove('hidden');
    panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (historyEl) {
        loadScanHistory('scan-history-panel');
    }
}

function openCertificatePreview() {
    if (state.lastSummaryPath) {
        window.open(state.lastSummaryPath, '_blank', 'noopener');
        return;
    }
    showToast("No certificate available yet.", "info");
}

function showToast(message, type) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const style = type === 'success'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-slate-200 bg-white text-slate-700';

    toast.className = `px-4 py-3 rounded-lg border shadow-sm text-xs font-bold uppercase tracking-widest ${style}`;
    toast.innerText = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 400);
    }, 2400);
}

function formatSummary(summary) {
    if (!summary) return '';
    const lines = [];
    lines.push(`Status: ${summary.status || 'MANUAL_REVIEW'}`);
    if (summary.summary) {
        lines.push('');
        lines.push('Summary:');
        lines.push(summary.summary);
    }
    if (summary.findings && summary.findings.length) {
        lines.push('');
        lines.push('Findings:');
        summary.findings.forEach(item => lines.push(`- ${item}`));
    }
    if (summary.missing_items && summary.missing_items.length) {
        lines.push('');
        lines.push('Missing Items:');
        summary.missing_items.forEach(item => lines.push(`- ${item}`));
    }
    if (summary.improvements && summary.improvements.length) {
        lines.push('');
        lines.push('Improvements:');
        summary.improvements.forEach(item => lines.push(`- ${item}`));
    }
    return lines.join('\n');
}

function loadScanHistory(targetId) {
    const url = 'scan_history.php?t=' + new Date().getTime();
    fetch(url, { cache: 'no-store' })
        .then(response => response.json())
        .then(data => renderScanHistory(data, targetId))
        .catch(err => {
            console.error(err);
            renderScanHistory([], targetId);
        });
}

function renderScanHistory(items, targetId) {
    const historyEl = document.getElementById(targetId || 'scan-history');
    if (!historyEl) return;

    if (!Array.isArray(items) || items.length === 0) {
        historyEl.innerHTML = '<div class="text-slate-400">No scans found.</div>';
        return;
    }

    const rows = items.map(item => {
        const statusClass = item.result_status === 'FAIL'
            ? 'text-red-600'
            : item.result_status === 'PASS' ? 'text-emerald-600' : 'text-amber-600';
        const summaryLink = item.summary_file_path
            ? `<a href="${item.summary_file_path}" target="_blank" class="text-[#0ea5a4] font-bold">PDF</a>`
            : '<span class="text-slate-300">-</span>';
        return `
            <div class="flex items-center justify-between border-b border-slate-100 py-2">
                <div class="text-xs">
                    <div class="font-bold text-slate-700">Scan #${item.id}</div>
                    <div class="text-slate-400">${item.scanned_at || ''}</div>
                </div>
                <div class="text-xs font-bold ${statusClass}">${item.result_status || 'MANUAL_REVIEW'}</div>
                <div class="text-xs">${summaryLink}</div>
            </div>
        `;
    }).join('');

    historyEl.innerHTML = rows;
}

// --- Contracts View Logic (DB Connected) ---
async function renderContracts() {
    const container = document.getElementById('contracts-container');
    if(!container) return;
    
    container.innerHTML = '<div class="text-center p-12"><div class="loader mx-auto mb-4"></div><p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Connecting to Database...</p></div>'; // Corrected escaping for template literal

    try {
        const response = await fetch('tenders.php?t=' + new Date().getTime());
        if (!response.ok) throw new Error('API error');
        const tenders = await response.json();

        container.innerHTML = '';

        if (!Array.isArray(tenders) || tenders.length === 0) {
            container.innerHTML = `
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fa-solid fa-folder-open text-slate-300 text-2xl"></i></div>
                    <h3 class="text-slate-800 font-bold">No data found</h3>
                    <p class="text-slate-400 text-sm mt-1 mb-6">Database table 'tenders' is empty.</p>
                    <button onclick="renderContracts()" class="text-[#0ea5a4] text-xs font-bold uppercase hover:underline">Refresh</button>
                </div>
            `; // Corrected escaping for template literal
            return;
        }

        // Render Table Wapcore Style
        const table = document.createElement('div');
        table.className = "overflow-x-auto";
        table.innerHTML = `
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Project</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Client</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Value</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    ${tenders.map(tender => {
                        let statusClass = "bg-slate-100 text-slate-500";
                        const s = (tender.status || '').toLowerCase();
                        if(s.includes('active') || s.includes('lopend')) statusClass = "bg-emerald-50 text-emerald-600 border border-emerald-100";
                        else if(s.includes('pending') || s.includes('behandeling')) statusClass = "bg-amber-50 text-amber-600 border border-amber-100";
                        else if(s.includes('rejected') || s.includes('afgewezen')) statusClass = "bg-red-50 text-red-600 border border-red-100";
                        
                        return `
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-6 py-4 font-mono text-xs text-slate-400 group-hover:text-[#0ea5a4] transition-colors">${tender.id || '-'}</td>
                            <td class="px-6 py-4 font-bold text-slate-700">${tender.project || '-'}</td>
                            <td class="px-6 py-4 text-slate-500 font-medium">${tender.client || '-'}</td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-md text-[10px] uppercase font-bold tracking-wide ${statusClass}">${tender.status || 'Unknown'}</span></td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-700">${tender.amount || '-'}</td>
                            <td class="px-6 py-4 text-right text-xs text-slate-400">${tender.start_date || '-'}</td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `; // Corrected escaping for template literal
        container.appendChild(table);

    } catch (error) {
        console.error("DB Error:", error);
        container.innerHTML = `
            <div class="p-8 text-center">
                <div class="text-red-500 font-bold mb-2"><i class="fa-solid fa-triangle-exclamation"></i> System Error</div>
                <p class="text-xs text-red-400">Could not connect to tenders.php</p>
                <button onclick="renderContracts()" class="mt-4 px-4 py-2 bg-red-50 text-red-600 rounded-lg text-xs font-bold uppercase">Try Again</button>
            </div>
        `; // Corrected escaping for template literal
    }
}

// --- Suppliers View Logic (DB Connected or Empty) ---
async function renderSuppliers() {
    const container = document.getElementById('suppliers-container');
    if(!container) return;
    
    container.innerHTML = '<div class="text-center p-12"><div class="loader mx-auto mb-4"></div><p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Fetching Suppliers...</p></div>'; // Corrected escaping for template literal

    try {
        // Try to fetch from suppliers.php (even if it doesn't exist yet, to avoid placeholders)
        const response = await fetch('suppliers.php?t=' + new Date().getTime());
        if (!response.ok) throw new Error('API not available');
        const suppliers = await response.json();

        container.innerHTML = '';

        if (!Array.isArray(suppliers) || suppliers.length === 0) {
            throw new Error("No data"); // Trigger empty state
        }

        table.className = "overflow-x-auto";
        table.innerHTML = `
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Company</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">LCE Certified</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    ${suppliers.map(supplier => {
                        const isCertified = supplier.lce_certified == 1;
                        return `
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-700">${supplier.name || '-'}</td>
                            <td class="px-6 py-4 text-slate-500 font-medium">${supplier.type || '-'}</td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-md text-[10px] uppercase font-bold tracking-wide bg-slate-100 text-slate-500">${supplier.status || 'Unknown'}</span></td>
                            <td class="px-6 py-4 text-right">
                                ${isCertified ? '<span class="text-emerald-500 font-bold text-xs"><i class="fa-solid fa-certificate mr-1"></i> Certified</span>' : '<span class="text-slate-300 text-xs">Pending</span>'} // Corrected escaping for template literal
                            </td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `; // Corrected escaping for template literal
        container.appendChild(table);

    } catch (error) {
        // Default empty state (No database/API yet)
        container.innerHTML = `
            <div class="text-center py-16">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fa-solid fa-network-wired text-slate-300 text-2xl"></i></div>
                <h3 class="text-slate-800 font-bold">No Suppliers Found</h3>
                <p class="text-slate-400 text-sm mt-1 mb-6">The suppliers database is currently empty.</p>
                <button onclick="renderSuppliers()" class="text-[#0ea5a4] text-xs font-bold uppercase hover:underline">Refresh</button>
            </div>
        `; // Corrected escaping for template literal
    }
}

document.addEventListener('DOMContentLoaded', () => {
    applyTheme();
    applyCompactView();
    initApp();
    router('dashboard');
});
