<!DOCTYPE html>

<html lang="nl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SuriCore - Local Content Engine Dashboard</title>

    <script src="https://cdn.tailwindcss.com" defer></script>

    <script src="https://unpkg.com/lucide@latest" defer></script>

    <link rel="stylesheet" href="/public/css/dashboard.css">

    <script src="/public/js/dashboard.js" defer></script>

</head>

<body class="bg-slate-50 text-slate-900 min-h-screen flex overflow-hidden">



    <!-- LOGIN MODAL (Wordt getoond als er geen sessie is) -->

    <div id="loginModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm modal-active">

        <div class="bg-white w-full max-w-md p-8 rounded-2xl shadow-2xl relative">

            <div class="flex flex-col items-center mb-6">

                <div class="bg-blue-600 p-3 rounded-xl shadow-lg mb-4">

                    <i data-lucide="shield-check" class="text-white w-8 h-8"></i>

                </div>

                <h2 class="text-2xl font-bold text-slate-800">SuriCore Login</h2>

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



    <!-- SIDEBAR -->

    <aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col h-screen">

        <div class="p-6">

            <h1 class="text-xl font-bold flex items-center gap-2 tracking-tight">

                <div class="bg-blue-600 p-1.5 rounded-lg">

                    <i data-lucide="shield-check" class="text-white w-5 h-5"></i>

                </div>

                SuriCore

            </h1>

            <p class="text-[10px] text-slate-400 mt-2 ml-1 uppercase tracking-widest font-semibold">Local Content Engine</p>

        </div>

        

        <nav class="flex-1 mt-2 px-4 space-y-1">

            <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-blue-600/10 text-blue-400 border border-blue-600/20 font-medium transition-all">

                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>

                <span>Dashboard</span>

            </a>

            <a href="#" class="flex items-center gap-3 p-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all">

                <i data-lucide="file-text" class="w-5 h-5"></i>

                <span>Documenten</span>

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

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 relative">

        

        <!-- HEADER -->

        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 flex-shrink-0 z-20 shadow-sm">

            <div class="flex items-center gap-4">

                <h2 class="text-lg font-bold text-slate-800">Bedrijfscompliance</h2>

            </div>

            <div class="flex items-center gap-4">

                <div id="connectionStatus" class="hidden text-xs font-medium px-2 py-1 rounded bg-green-100 text-green-700 flex items-center gap-1">

                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Verbonden

                </div>

            </div>

        </header>



        <!-- DASHBOARD CONTENT -->

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth" id="dashboardContent" style="opacity: 0.3; pointer-events: none;">

            <div class="max-w-7xl mx-auto space-y-8">

                

                <!-- KPI GRID -->

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    

                    <!-- SCORE GAUGE -->

                    <div class="lg:col-span-4 bg-white p-8 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center justify-center relative overflow-hidden">

                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-green-500"></div>

                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">Totale Compliance Score</h3>

                        

                        <div class="relative w-56 h-56">

                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">

                                <circle class="text-slate-100 stroke-current" stroke-width="8" cx="50" cy="50" r="40" fill="transparent"></circle>

                                <circle id="gaugeProgress" class="text-slate-300 stroke-current gauge-ring" stroke-width="8" stroke-linecap="round" cx="50" cy="50" r="40" fill="transparent" stroke-dasharray="251.2" stroke-dashoffset="251.2"></circle>

                            </svg>

                            <div class="absolute inset-0 flex flex-col items-center justify-center">

                                <span class="text-5xl font-black text-slate-800 tracking-tight" id="scoreDisplay">--</span>

                                <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded mt-2" id="scoreLabel">...</span>

                            </div>

                        </div>

                        <p class="text-center text-slate-500 mt-6 text-sm" id="scoreMessage">Wachten op server data...</p>

                    </div>

                    <!-- ACTION REQUIRED -->
                    <div class="lg:col-span-8 bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hidden" id="actionRequiredPanel">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Action Required</p>
                                <h3 class="text-lg font-bold text-slate-800">Documenten bijna verlopen</h3>
                                <p class="text-sm text-slate-500" id="actionRequiredMsg">Er zijn documenten die binnen 30 dagen verlopen.</p>
                            </div>
                            <button type="button" id="actionRequiredCta" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                                <i data-lucide="alert-triangle" class="w-4 h-4"></i> Actie ondernemen
                            </button>
                        </div>
                    </div>

                    <!-- STATISTICS -->

                    <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">

                            <div class="flex justify-between items-start mb-4">

                                <div class="p-3 bg-green-50 text-green-600 rounded-xl"><i data-lucide="check-circle" class="w-6 h-6"></i></div>

                                <span class="text-slate-400 text-xs">Gevalideerd</span>

                            </div>

                            <h4 class="text-3xl font-bold text-slate-800" id="statValid">-</h4>

                        </div>



                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">

                            <div class="flex justify-between items-start mb-4">

                                <div class="p-3 bg-orange-50 text-orange-600 rounded-xl"><i data-lucide="alert-triangle" class="w-6 h-6"></i></div>

                                <span class="text-slate-400 text-xs">Review Nodig</span>

                            </div>

                            <h4 class="text-3xl font-bold text-slate-800" id="statReview">-</h4>

                        </div>



                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">

                            <div class="flex justify-between items-start mb-4">

                                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl"><i data-lucide="loader-2" class="w-6 h-6"></i></div>

                                <span class="text-slate-400 text-xs">In Verwerking</span>

                            </div>

                            <h4 class="text-3xl font-bold text-slate-800" id="statProcessing">-</h4>

                        </div>



                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">

                            <div class="flex justify-between items-start mb-4">

                                <div class="p-3 bg-red-50 text-red-600 rounded-xl"><i data-lucide="x-circle" class="w-6 h-6"></i></div>

                                <span class="text-slate-400 text-xs">Ongeldig / Verlopen</span>

                            </div>

                            <h4 class="text-3xl font-bold text-slate-800" id="statInvalid">-</h4>

                        </div>

                    </div>

                </div>



                <!-- DOCUMENTS TABLE -->

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" id="documentsSection">

                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">

                        <div class="flex flex-col lg:flex-row justify-between items-start gap-4">

                            <div>

                                <h3 class="font-bold text-slate-800 text-lg">Documentenkluis</h3>

                                <p class="text-sm text-slate-500">Upload documenten en volg de status in real-time.</p>

                            </div>

                            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">

                                <select id="categorySelect" class="w-full sm:w-56 px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                    <option value="">Kies documenttype</option>
                                </select>

                                <button type="button" id="uploadBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md justify-center">
                                    <i data-lucide="upload-cloud" class="w-4 h-4"></i> Document Uploaden
                                </button>

                                <button type="button" id="cameraBtn" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md justify-center">
                                    <i data-lucide="camera" class="w-4 h-4"></i> Camera
                                </button>

                                <input type="file" id="fileInput" class="hidden" accept="image/*,application/pdf" onchange="handleFileInputChange(event)">
                                <input type="file" id="cameraInput" class="hidden" accept="image/*" capture="environment" onchange="handleFileInputChange(event)">

                            </div>

                        </div>

                        <div id="uploadDropzone" class="mt-4 rounded-2xl border-2 border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-500 transition">
                            <div class="flex items-center justify-center gap-2 text-slate-600 font-medium">
                                <i data-lucide="mouse-pointer-click" class="w-4 h-4"></i>
                                <span>Sleep je document hierheen of klik op “Document Uploaden”</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Ondersteund: PDF of foto (JPG/PNG)</p>
                            <p class="text-xs text-slate-400 mt-1" id="uploadFilename"></p>
                        </div>

                    </div>



                    <div class="overflow-x-auto">

                        <table class="w-full text-left">

                            <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">

                                <tr>

                                    <th class="px-6 py-4">Document Naam</th>

                                    <th class="px-6 py-4 text-center">Status</th>

                                    <th class="px-6 py-4">Datum</th>

                                    <th class="px-6 py-4 text-right">Actie</th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100 text-sm" id="documentsTableBody">

                                <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400 italic">Verbinding maken met API...</td></tr>

                            </tbody>

                        </table>

                    </div>

                </div>

                <!-- COMPANY PROFILE -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg">Bedrijfsprofiel</h3>
                            <p class="text-sm text-slate-500">Vul je gegevens in en download je profiel-PDF.</p>
                        </div>
                        <button id="downloadProfileBtn" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                            <i data-lucide="download" class="w-4 h-4"></i> Download Profiel
                        </button>
                    </div>

                    <form id="companyProfileForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyNameInput">Bedrijfsnaam</label>
                            <input type="text" id="companyNameInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Bedrijf BV" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1" for="companySectorInput">Sector</label>
                            <input type="text" id="companySectorInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Olie & Gas" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyExperienceInput">Ervaring</label>
                            <input type="text" id="companyExperienceInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="5+ jaar" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyContactInput">Contact</label>
                            <input type="text" id="companyContactInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="info@bedrijf.sr" required>
                        </div>

                        <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 pt-2">
                            <button type="submit" id="saveProfileBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                                <i data-lucide="save" class="w-4 h-4"></i> Profiel Opslaan
                            </button>
                            <div id="profileStatus" class="text-sm text-slate-500 flex items-center"></div>
                        </div>
                    </form>
                </div>

                <!-- DIGITAL ID -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg">Digital ID</h3>
                            <p class="text-sm text-slate-500">Maak je publieke profiel en deel het via WhatsApp.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a id="publicProfileLink" class="text-sm text-blue-600 hover:text-blue-700 font-semibold" href="#" target="_blank" rel="noopener">Bekijk publieke link</a>
                        </div>
                    </div>
                    <form id="digitalIdForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1" for="publicSlugInput">Publieke slug</label>
                            <input type="text" id="publicSlugInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="sarah-consult">
                            <p class="text-xs text-slate-400 mt-1">Voorbeeld: suricore.sr/p/sarah-consult</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1" for="displayNameInput">Weergavenaam</label>
                            <input type="text" id="displayNameInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Sarah Consult">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1" for="addressInput">Adres</label>
                            <input type="text" id="addressInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Paramaribo, Suriname">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1" for="latInput">Lat</label>
                                <input type="text" id="latInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="5.8520">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1" for="lngInput">Lng</label>
                                <input type="text" id="lngInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="-55.2038">
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <button type="button" id="geocodeBtn" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                                Locatie automatisch invullen
                            </button>
                            <span id="geocodeStatus" class="text-xs text-slate-400 ml-3"></span>
                        </div>
                        <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 pt-2">
                            <button type="submit" id="saveDigitalIdBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                                <i data-lucide="save" class="w-4 h-4"></i> Digital ID Opslaan
                            </button>
                            <button type="button" id="uploadPhotoBtn" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                                <i data-lucide="image" class="w-4 h-4"></i> Profielfoto uploaden
                            </button>
                            <input type="file" id="profilePhotoInput" class="hidden" accept="image/*">
                            <div id="digitalIdStatus" class="text-sm text-slate-500 flex items-center"></div>
                        </div>
                    </form>
                </div>

                <!-- UPGRADE -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg">Upgrade & Betaling</h3>
                            <p class="text-sm text-slate-500">Upgrade om AI‑analyse en extra functies te ontgrendelen.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-200 text-slate-700" id="planBadge">PLAN: FREE</span>
                            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-200 text-slate-700" id="planStatusBadge">STATUS: ACTIVE</span>
                        </div>
                    </div>
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="p-4 rounded-xl border border-slate-200 bg-white">
                            <h4 class="font-bold text-slate-800">FREE</h4>
                            <p class="text-sm text-slate-500 mt-1">Uploaden en basisstatus</p>
                            <p class="text-xs text-slate-400 mt-2">AI‑analyse: niet beschikbaar</p>
                        </div>
                        <div class="p-4 rounded-xl border border-slate-200 bg-white">
                            <h4 class="font-bold text-slate-800">PRO</h4>
                            <p class="text-sm text-slate-500 mt-1">AI‑analyse + advies</p>
                            <p class="text-xs text-slate-400 mt-2">Voor compliance checks</p>
                        </div>
                        <div class="p-4 rounded-xl border border-slate-200 bg-white">
                            <h4 class="font-bold text-slate-800">BUSINESS</h4>
                            <p class="text-sm text-slate-500 mt-1">Alles in PRO + Tender details</p>
                            <p class="text-xs text-slate-400 mt-2">Voor grotere bedrijven</p>
                        </div>
                    </div>
                    <div class="p-6 border-t border-slate-100">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                                <p class="text-sm font-semibold text-slate-700">Bankgegevens</p>
                                <p class="text-sm text-slate-500 mt-1">Bank: TCB</p>
                                <p class="text-sm text-slate-500">Rekening: 12.34.56.789</p>
                                <p class="text-sm text-slate-500">Naam: Wapcomtek NV</p>
                                <p class="text-xs text-slate-400 mt-2">Stuur een screenshot van je betaling hieronder.</p>
                            </div>
                            <div class="p-4 rounded-xl border border-slate-200 bg-white">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">Betaalbewijs uploaden</p>
                                        <p class="text-xs text-slate-400">JPG/PNG/PDF</p>
                                    </div>
                                    <button type="button" id="paymentUploadBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                                        <i data-lucide="upload" class="w-4 h-4"></i> Upload bewijs
                                    </button>
                                    <input type="file" id="paymentInput" class="hidden" accept="image/*,application/pdf">
                                </div>
                                <div class="mt-3 text-sm text-slate-500" id="paymentStatus">Nog geen betaalbewijs geüpload.</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </main>



    <!-- TOAST MESSAGE -->

    <div id="toast" class="fixed bottom-6 right-6 bg-slate-800 text-white px-6 py-4 rounded-xl shadow-2xl transform translate-y-32 transition-transform duration-300 flex items-center gap-3 z-50">

        <i id="toastIcon" data-lucide="info"></i>

        <span class="font-medium" id="toastMsg">Bericht</span>

    </div>


</body>

</html>
