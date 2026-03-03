<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Wapcore LCE Dashboard')</title>

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
                <img src="/public/img/logo-lce.png" alt="Wapcore LCE logo" class="w-20 h-auto object-contain mb-4">
                <h2 class="text-2xl font-bold text-slate-800">Wapcore Login</h2>
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
            </form>
        </div>
    </div>

    <!-- MOBILE TOPBAR -->
    <header class="md:hidden fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-200 flex items-center justify-between px-4 h-14">
        <button id="mobileMenuBtn" class="p-2 rounded-lg border border-slate-200 text-slate-700">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <img src="/public/img/logo-lce.png" alt="Wapcore LCE logo" class="w-28 h-auto object-contain">
        <div class="w-9"></div>
    </header>

    <!-- MOBILE DRAWER -->
    <div id="mobileMenuOverlay" class="fixed inset-0 z-40 bg-slate-900/60 hidden md:hidden"></div>
    <aside id="mobileMenu" class="fixed top-0 left-0 bottom-0 w-72 bg-slate-900 text-white z-50 transform -translate-x-full transition-transform md:hidden">
        <div class="p-6">
            <img src="/public/img/logo-lce.png" alt="Wapcore LCE logo" class="w-32 h-auto object-contain">
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
            <button onclick="handleLogout()" class="text-xs text-slate-400 hover:text-red-400 transition flex items-center gap-1 mt-0.5">
                <i data-lucide="log-out" class="w-3 h-3"></i> Uitloggen
            </button>
        </div>
    </aside>

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col h-screen">
        <div class="p-6">
            <img src="/public/img/logo-lce.png" alt="Wapcore LCE logo" class="w-36 h-auto object-contain">
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
            <div class="flex items-center gap-4">
                <div id="connectionStatus" class="hidden text-xs font-medium px-2 py-1 rounded bg-green-100 text-green-700 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Verbonden
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth" id="dashboardContent">
            @yield('content')
        </div>
    </main>

    <!-- TOAST MESSAGE -->
    <div id="toast" class="fixed bottom-6 right-6 bg-slate-800 text-white px-6 py-4 rounded-xl shadow-2xl transform translate-y-32 transition-transform duration-300 flex items-center gap-3 z-50">
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
        mobileOverlay?.addEventListener('click', () => toggleMobileMenu(false));
    </script>
    @yield('scripts')
</body>
</html>
