<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SuriCore LCE Dashboard')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <script src="/public/js/dashboard.js" defer></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex overflow-hidden">

    <!-- LOGIN MODAL -->
    <div id="loginModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm hidden modal-enter">
        <div class="bg-white w-full max-w-md p-8 rounded-2xl shadow-2xl relative">
            <div class="flex flex-col items-center mb-6">
                <img src="/public/img/logo-lce.png" alt="SuriCore LCE logo" class="w-20 h-auto object-contain mb-4">
                <h2 class="text-2xl font-bold text-slate-800">SuriCore LCE Login</h2>
                <p class="text-slate-500 text-sm mt-1">Log in om toegang te krijgen tot de Local Content Engine</p>
            </div>
            <form id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">E-mailadres</label>
                    <input type="email" id="emailInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="naam@bedrijf.sr" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Wachtwoord</label>
                    <input type="password" id="passwordInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="••••••••" required>
                </div>
                <div id="loginError" class="text-red-600 text-sm text-center hidden font-medium bg-red-50 p-3 rounded-lg border border-red-100"></div>
                <button type="submit" id="loginBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition shadow-md shadow-blue-200 flex justify-center items-center gap-2">
                    Inloggen
                </button>
                <div id="oauthButtons" class="hidden oauth-stack">
                    <a id="googleLoginBtn" href="#" class="hidden oauth-login-btn oauth-google">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M21.35 12.23c0-.73-.06-1.43-.18-2.1H12v3.98h5.24a4.48 4.48 0 0 1-1.94 2.94v2.44h3.13c1.84-1.7 2.92-4.2 2.92-7.26Z" fill="#4285F4"/>
                            <path d="M12 21.75c2.63 0 4.83-.87 6.44-2.36l-3.13-2.44c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.52A9.74 9.74 0 0 0 12 21.75Z" fill="#34A853"/>
                            <path d="M6.54 13.84a5.86 5.86 0 0 1 0-3.68V7.64H3.3a9.75 9.75 0 0 0 0 8.72l3.24-2.52Z" fill="#FBBC05"/>
                            <path d="M12 6.13c1.43 0 2.72.49 3.73 1.45l2.8-2.8C16.83 3.19 14.63 2.25 12 2.25A9.74 9.74 0 0 0 3.3 7.64l3.24 2.52c.77-2.31 2.92-4.03 5.46-4.03Z" fill="#EA4335"/>
                        </svg>
                        <span>Inloggen met Google</span>
                    </a>
                    <a id="microsoftLoginBtn" href="#" class="hidden oauth-login-btn oauth-microsoft">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="3" y="3" width="8" height="8" rx="1" fill="#F25022"/>
                            <rect x="13" y="3" width="8" height="8" rx="1" fill="#7FBA00"/>
                            <rect x="3" y="13" width="8" height="8" rx="1" fill="#00A4EF"/>
                            <rect x="13" y="13" width="8" height="8" rx="1" fill="#FFB900"/>
                        </svg>
                        <span>Inloggen met Microsoft</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- MOBILE TOPBAR -->
    <header class="md:hidden fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-200 flex items-center justify-between px-4 h-14">
        <button id="mobileMenuBtn" class="p-2 rounded-lg border border-slate-200 text-slate-700">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <img src="/public/img/logo-lce.png" alt="SuriCore LCE logo" class="w-28 h-auto object-contain">
        <div class="w-9"></div>
    </header>

    <!-- MOBILE DRAWER -->
    <div id="mobileMenuOverlay" class="fixed inset-0 z-40 bg-slate-900/60 hidden md:hidden"></div>
    <aside id="mobileMenu" class="fixed top-0 left-0 bottom-0 w-72 max-w-[85vw] bg-slate-900 text-white z-50 transform -translate-x-full transition-transform md:hidden flex flex-col h-[100dvh] overflow-y-auto overscroll-contain">
        <div class="p-6 flex items-center justify-between gap-3 border-b border-slate-800">
            <img src="/public/img/logo-lce.png" alt="SuriCore LCE logo" class="w-32 h-auto object-contain">
            <button type="button" id="mobileMenuCloseBtn" class="p-2 rounded-lg border border-slate-700 text-slate-300 hover:text-white hover:border-slate-500">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <nav class="flex-1 mt-2 px-4 pb-4 space-y-1">
            <a href="/dashboard" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'dashboard' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>Dashboard</span>
            </a>
            <a href="/documents" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'documents' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="file-text" class="w-5 h-5"></i>
                <span>Documenten</span>
            </a>
            <a href="/user/tenders" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'tenders' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="briefcase" class="w-5 h-5"></i>
                <span>Aanbestedingen</span>
            </a>
            <a href="/profile" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'profile' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="building" class="w-5 h-5"></i>
                <span>Bedrijfsprofiel</span>
            </a>
            <a href="/digital-id" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'digital-id' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="id-card" class="w-5 h-5"></i>
                <span>Digitale ID</span>
            </a>
            <a href="/upgrade" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'upgrade' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
                <span>Upgrade</span>
            </a>
        </nav>
        <div class="p-4 border-t border-slate-800 bg-slate-950/30 sticky bottom-0">
            <button onclick="handleLogout()" class="text-xs text-slate-400 hover:text-red-400 transition flex items-center gap-1 mt-0.5">
                <i data-lucide="log-out" class="w-3 h-3"></i> Uitloggen
            </button>
        </div>
    </aside>

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col h-screen">
        <div class="p-6">
            <img src="/public/img/logo-lce.png" alt="SuriCore LCE logo" class="w-36 h-auto object-contain">
        </div>
        <nav class="flex-1 mt-2 px-4 space-y-1">
            <a href="/dashboard" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'dashboard' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>Dashboard</span>
            </a>
            <a href="/documents" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'documents' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="file-text" class="w-5 h-5"></i>
                <span>Documenten</span>
            </a>
            <a href="/user/tenders" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'tenders' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="briefcase" class="w-5 h-5"></i>
                <span>Aanbestedingen</span>
            </a>
            <a href="/profile" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'profile' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="building" class="w-5 h-5"></i>
                <span>Bedrijfsprofiel</span>
            </a>
            <a href="/digital-id" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'digital-id' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="id-card" class="w-5 h-5"></i>
                <span>Digitale ID</span>
            </a>
            <a href="/upgrade" class="flex items-center gap-3 p-3 rounded-xl {{ ($active ?? '') === 'upgrade' ? 'bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
                <span>Upgrade</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/30">
            <div class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-slate-800 transition">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-lg text-xs" id="userInitials"></div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate" id="userNameDisplay">--</p>
                    <button onclick="handleLogout()" class="text-xs text-slate-400 hover:text-red-400 transition flex items-center gap-1 mt-0.5">
                        <i data-lucide="log-out" class="w-3 h-3"></i> Uitloggen
                    </button>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 relative pt-14 md:pt-0">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 flex-shrink-0 z-20 shadow-sm">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-bold text-slate-800">@yield('page_title')</h2>
            </div>
            <div class="hidden sm:flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2">
                    <a href="/dashboard" class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition {{ ($active ?? '') === 'dashboard' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-600 hover:bg-slate-100' }}">Dashboard</a>
                    <a href="/documents" class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition {{ ($active ?? '') === 'documents' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-600 hover:bg-slate-100' }}">Documenten</a>
                </div>
                <div id="connectionStatus" class="hidden text-xs font-medium px-2 py-1 rounded bg-green-100 text-green-700 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Verbonden
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto px-4 pt-6 pb-28 md:p-8 scroll-smooth" id="dashboardContent">
            @yield('content')
        </div>
    </main>

    <!-- TOAST MESSAGE -->
    <div id="toast" class="fixed bottom-4 right-4 left-4 md:left-auto md:bottom-6 md:right-6 bg-slate-800 text-white px-6 py-4 rounded-xl shadow-2xl transform translate-y-32 transition-transform duration-300 flex items-center gap-3 z-50">
        <i id="toastIcon" data-lucide="info"></i>
        <span class="font-medium" id="toastMsg">Bericht</span>
    </div>

    <div id="complianceProgressModal" class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm p-4">
        <div class="max-w-xl mx-auto mt-16 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                <div>
                    <h3 id="complianceProgressTitle" class="text-lg font-bold text-slate-800">Voortgang</h3>
                    <p id="complianceProgressSubtitle" class="text-sm text-slate-500">Update</p>
                </div>
                <button type="button" id="complianceProgressClose" class="text-slate-400 hover:text-slate-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div id="complianceResultWrap" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Laatste upload status</p>
                        <span id="complianceResultStatus" class="text-xs font-semibold px-2 py-1 rounded-full bg-slate-200 text-slate-700">--</span>
                    </div>
                    <p id="complianceResultReason" class="text-xs text-slate-600 mt-2"></p>
                </div>
                <div>
                    <div class="flex items-center justify-between text-sm font-semibold text-slate-700">
                        <span id="complianceCurrentLevelLabel">Level</span>
                        <span id="complianceCurrentPercent">0%</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-200 overflow-hidden">
                        <div id="complianceCurrentBar" class="h-2 bg-blue-600 transition-all" style="width:0%"></div>
                    </div>
                    <p id="complianceCurrentMeta" class="text-xs text-slate-500 mt-2"></p>
                    <p id="complianceCurrentMissing" class="text-xs text-slate-600 mt-1"></p>
                </div>
                <div id="complianceNextWrap" class="hidden border-t border-slate-100 pt-4">
                    <div class="flex items-center justify-between text-sm font-semibold text-slate-700">
                        <span id="complianceNextLevelLabel">Volgend level</span>
                        <span id="complianceNextPercent">0%</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-200 overflow-hidden">
                        <div id="complianceNextBar" class="h-2 bg-emerald-600 transition-all" style="width:0%"></div>
                    </div>
                    <p id="complianceNextMeta" class="text-xs text-slate-500 mt-2"></p>
                    <p id="complianceNextMissing" class="text-xs text-slate-600 mt-1"></p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end">
                <button type="button" id="complianceProgressOk" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Sluiten</button>
            </div>
        </div>
    </div>

    <script>
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileOverlay = document.getElementById('mobileMenuOverlay');
        const mobileCloseBtn = document.getElementById('mobileMenuCloseBtn');
        const toggleMobileMenu = (open) => {
            if (!mobileMenu || !mobileOverlay) return;
            if (open) {
                mobileMenu.classList.remove('-translate-x-full');
                mobileOverlay.classList.remove('hidden');
            } else {
                mobileMenu.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
            }
        };
        mobileBtn?.addEventListener('click', () => toggleMobileMenu(true));
        mobileCloseBtn?.addEventListener('click', () => toggleMobileMenu(false));
        mobileOverlay?.addEventListener('click', () => toggleMobileMenu(false));
    </script>
    @yield('scripts')
</body>
</html>
