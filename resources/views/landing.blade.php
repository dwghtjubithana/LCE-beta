<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wapcore LCE | SuriCore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --wap-turquoise: #0ea5a4;
            --wap-turquoise-dark: #0c8e8d;
            --wap-soft-bg: #f4f7f9;
            --wap-sidebar: #1e293b;
            --wap-card: #ffffff;
            --wap-text: #334155;
            --wap-muted: #64748b;
            --wap-border: #e2e8f0;
            --ok: #16a34a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", system-ui, sans-serif;
            color: var(--wap-text);
            background:
                radial-gradient(circle at 10% -10%, rgba(14, 165, 164, 0.22), transparent 40%),
                radial-gradient(circle at 100% 0%, rgba(30, 41, 59, 0.20), transparent 50%),
                var(--wap-soft-bg);
        }
        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 20px 60px;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 28px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--wap-sidebar);
            color: var(--wap-turquoise);
            display: grid;
            place-items: center;
            font-weight: 900;
        }
        .brand-text {
            margin: 0;
            font-size: 20px;
            font-weight: 300;
            letter-spacing: -0.02em;
            color: #0f172a;
        }
        .brand-text b {
            font-weight: 900;
            color: var(--wap-turquoise);
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 11px 16px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: all .2s ease;
        }
        .btn-primary {
            background: var(--wap-turquoise);
            color: #fff;
            box-shadow: 0 10px 20px rgba(14, 165, 164, 0.2);
        }
        .btn-primary:hover { background: var(--wap-turquoise-dark); }
        .btn-ghost {
            background: #fff;
            border-color: var(--wap-border);
            color: var(--wap-text);
        }
        .btn-ghost:hover { border-color: #cbd5e1; }
        .hero {
            padding: 40px 0 46px;
            margin-bottom: 10px;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 30px;
            align-items: center;
        }
        .hero-main {
            padding: 0;
        }
        .eyebrow {
            margin: 0 0 10px;
            font-size: 11px;
            color: var(--wap-turquoise);
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-weight: 800;
        }
        h1 {
            margin: 0 0 12px;
            line-height: 1.15;
            font-size: clamp(38px, 6vw, 68px);
            letter-spacing: -0.03em;
            color: #0f172a;
        }
        .hero-main p {
            margin: 0;
            color: var(--wap-muted);
            line-height: 1.7;
            font-size: 15px;
            max-width: 62ch;
        }
        .hero-side {
            position: relative;
            min-height: 260px;
            display: grid;
            place-items: center;
            padding: 20px;
            background: radial-gradient(circle at 65% 35%, rgba(14,165,164,.18), transparent 52%);
        }
        .glass-gauge {
            width: min(360px, 100%);
            border-radius: 12px;
            border-top: 1px solid rgba(14, 165, 164, 0.45);
            border-left: 1px solid rgba(255, 255, 255, 0.55);
            border-right: 1px solid rgba(255, 255, 255, 0.55);
            border-bottom: 1px solid rgba(219, 231, 238, 0.8);
            background: rgba(255, 255, 255, 0.56);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 24px 38px rgba(15, 23, 42, 0.14);
            padding: 20px 20px 18px;
        }
        .glass-gauge h3 {
            margin: 0 0 10px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #64748b;
        }
        .score-value {
            margin: 0;
            font-size: 34px;
            font-weight: 900;
            color: var(--wap-turquoise);
            letter-spacing: -0.02em;
        }
        .score-note {
            margin: 6px 0 0;
            font-size: 13px;
            color: #64748b;
        }
        .section-block {
            margin-top: 18px;
            padding: 34px;
            border-radius: 12px;
        }
        .section-features {
            background: linear-gradient(180deg, rgba(255,255,255,0.72) 0%, rgba(248,250,252,0.9) 100%);
        }
        .section-process {
            background: linear-gradient(180deg, rgba(236,253,252,0.58) 0%, rgba(244,247,249,0.88) 100%);
        }
        .section-title {
            margin: 0 0 18px;
            color: #0f172a;
            font-size: 28px;
            letter-spacing: -0.02em;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .surface-card {
            border: 1px solid #e2e8f0;
            border-top: 1px solid rgba(14, 165, 164, 0.5);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }
        .feature-item {
            border: 1px solid var(--wap-border);
            border-top: 1px solid rgba(14, 165, 164, 0.5);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            padding: 22px;
            display: grid;
            gap: 10px;
        }
        .feature-item h3 {
            margin: 0 0 12px;
            color: #0f172a;
            font-size: 18px;
            letter-spacing: -0.02em;
        }
        .feature-item p {
            margin: 0;
            color: var(--wap-muted);
            line-height: 1.7;
        }
        .feature-icon {
            width: 28px;
            height: 28px;
            color: var(--wap-turquoise);
        }
        .timeline {
            position: relative;
            display: flex;
            gap: 14px;
            align-items: stretch;
        }
        .timeline::before {
            content: "";
            position: absolute;
            left: 6%;
            right: 6%;
            top: 34px;
            height: 1px;
            background: rgba(14, 165, 164, 0.45);
            z-index: 0;
        }
        .step {
            position: relative;
            flex: 1;
            padding-top: 14px;
            z-index: 1;
        }
        .step-inner {
            height: 100%;
            padding: 44px 14px 14px;
        }
        .step-dot {
            position: absolute;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid rgba(14, 165, 164, 0.35);
            background: #fff;
            color: var(--wap-turquoise);
            display: grid;
            place-items: center;
            font-weight: 800;
        }
        .step h3 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #0f172a;
            text-align: center;
        }
        .step p {
            margin: 0;
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            text-align: center;
        }
        .footer-cta {
            margin-top: 20px;
            padding: 58px 20px;
            border-radius: 12px;
            background: radial-gradient(circle at 50% 42%, rgba(14,165,164,0.2) 0%, rgba(244,247,249,0.85) 45%, rgba(244,247,249,0.96) 100%);
        }
        .lead-card {
            width: min(640px, 100%);
            margin: 0 auto;
            padding: 26px 24px;
            text-align: center;
        }
        .lead-card h2 {
            margin: 0 0 10px;
            color: #0f172a;
            letter-spacing: -0.02em;
            font-size: 30px;
        }
        .lead-card p {
            margin: 0 0 20px;
            color: #475569;
            line-height: 1.7;
        }
        .lead-input {
            width: 100%;
            max-width: 420px;
            margin: 0 auto 12px;
            padding: 12px 13px;
            border-radius: 12px;
            border: 1px solid #dbe7ee;
            background: #fff;
            outline: none;
            font-size: 14px;
            font-family: inherit;
        }
        .lead-input:focus {
            border-color: rgba(14,165,164,.75);
            box-shadow: 0 0 0 3px rgba(14,165,164,.14);
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 30;
        }
        .modal.open { display: flex; }
        .modal-card {
            width: min(460px, 100%);
            background: #fff;
            border: 1px solid var(--wap-border);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 25px 40px rgba(15, 23, 42, 0.18);
        }
        .modal-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .modal-head h3 {
            margin: 0;
            font-size: 20px;
            letter-spacing: -0.02em;
            color: #0f172a;
        }
        .modal-head p {
            margin: 5px 0 0;
            color: var(--wap-muted);
            font-size: 14px;
        }
        .close {
            background: #f8fafc;
            border: 1px solid var(--wap-border);
            border-radius: 10px;
            padding: 7px 10px;
            cursor: pointer;
            color: #475569;
            font-weight: 700;
        }
        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 12px;
        }
        .field label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            font-weight: 700;
        }
        .field input {
            width: 100%;
            padding: 12px 13px;
            border-radius: 10px;
            border: 1px solid var(--wap-border);
            outline: none;
            font-size: 14px;
        }
        .field input:focus {
            border-color: rgba(14,165,164,.75);
            box-shadow: 0 0 0 3px rgba(14,165,164,.14);
        }
        .status {
            min-height: 20px;
            font-size: 13px;
            color: #ef4444;
            margin-top: 6px;
        }
        .status.ok { color: #059669; }

        @media (max-width: 940px) {
            .hero-grid { grid-template-columns: 1fr; }
            .hero-side { min-height: 220px; }
            .feature-grid { grid-template-columns: 1fr; }
            .timeline {
                flex-direction: column;
                gap: 12px;
            }
            .timeline::before {
                left: 23px;
                right: auto;
                top: 38px;
                bottom: 30px;
                width: 1px;
                height: auto;
            }
            .step {
                padding-top: 0;
                padding-left: 44px;
            }
            .step-dot {
                left: 5px;
                transform: none;
                top: 16px;
            }
            .step-inner {
                padding: 14px 14px 14px 18px;
            }
            .step h3, .step p { text-align: left; }
        }
        @media (max-width: 640px) {
            .wrap { padding: 14px 14px 32px; }
            .actions { width: 100%; }
            .actions .btn { flex: 1; }
            .hero { padding-top: 24px; }
            .section-block { padding: 20px 16px; }
            .lead-card { padding: 22px 14px; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">W</div>
                <p class="brand-text">Wap<b>core</b> LCE</p>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-open="login">Inloggen</button>
                <button type="button" class="btn btn-primary" data-open="register">Registreren</button>
            </div>
        </header>

        <section class="hero">
            <div class="hero-grid">
                <div class="hero-main">
                    <p class="eyebrow">Offshore Readiness Platform</p>
                    <h1>Claim uw plek in de Olie & Gas met SuriCore LCE!</h1>
                    <p>
                        Surinaamse vakmanschap verdient een plek aan de top. Maar zonder de juiste compliance
                        blijven de deuren van de grote multinationals gesloten. SuriCore LCE is uw digitale
                        snelweg naar een "Offshore Ready" status.
                    </p>
                </div>
                <div class="hero-side">
                    <div class="glass-gauge">
                        <h3>Compliance Gauge</h3>
                        <p class="score-value">Realtime</p>
                        <p class="score-note">Van document-scan naar zichtbaarheid voor inkopers in een heldere workflow.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-block section-features">
            <h2 class="section-title">Waarom SuriCore LCE?</h2>
            <div class="feature-grid">
                <article class="feature-item">
                    <svg class="feature-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 12h7M4 7h11M4 17h4M16 7l4 4-4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Efficiency</h3>
                    <p>Geen papieren rompslomp: onze LCE scant uw documenten (KKF, verzekeringen, HSE) en vertelt u direct wat er mist.</p>
                </article>
                <article class="feature-item">
                    <svg class="feature-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3v18M3 12h18M5.6 5.6c3.5 3.5 9.3 3.5 12.8 0M5.6 18.4c3.5-3.5 9.3-3.5 12.8 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Global Standards</h3>
                    <p>Wereldstandaard: wij toetsen u aan de internationale IOGP-423 normen die Tier 1 contractors eisen.</p>
                </article>
                <article class="feature-item">
                    <svg class="feature-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 12s3.5-5 9-5 9 5 9 5-3.5 5-9 5-9-5-9-5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="2.4" stroke="currentColor" stroke-width="1.7"/>
                    </svg>
                    <h3>Visibility</h3>
                    <p>Zichtbaarheid: word direct vindbaar voor inkopers zodra uw Compliance Gauge op groen staat.</p>
                </article>
                <article class="feature-item">
                    <svg class="feature-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3 5 6v5c0 4.3 2.8 8.2 7 9.5 4.2-1.3 7-5.2 7-9.5V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="m9.5 12 1.7 1.7 3.3-3.4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Legal & Local</h3>
                    <p>Lokaal en juridisch: volledig afgestemd op de Surinaamse wetgeving en ondersteund door KKF en VSB.</p>
                </article>
            </div>
        </section>

        <section class="section-block section-process">
            <h2 class="section-title">Hoe werkt het?</h2>
            <div class="timeline">
                <article class="step">
                    <div class="step-dot">1</div>
                    <div class="step-inner surface-card">
                        <h3>Quick Scan</h3>
                        <p>Upload uw huidige bedrijfsdocumenten via onze eenvoudige portal.</p>
                    </div>
                </article>
                <article class="step">
                    <div class="step-dot">2</div>
                    <div class="step-inner surface-card">
                        <h3>Gap-Analyse</h3>
                        <p>Onze Engine identificeert binnen minuten welke certificeringen of details u nog mist.</p>
                    </div>
                </article>
                <article class="step">
                    <div class="step-dot">3</div>
                    <div class="step-inner surface-card">
                        <h3>Fix &amp; Optimize</h3>
                        <p>Verbeter uw profiel met gericht advies, zoals ISO-tips en bedrijfspresentatie.</p>
                    </div>
                </article>
                <article class="step">
                    <div class="step-dot">4</div>
                    <div class="step-inner surface-card">
                        <h3>Validatie</h3>
                        <p>Ontvang uw digitale Local Content Seal zodra uw dossier compleet is.</p>
                    </div>
                </article>
                <article class="step">
                    <div class="step-dot">5</div>
                    <div class="step-inner surface-card">
                        <h3>Matching</h3>
                        <p>Uw bedrijf wordt gepresenteerd aan grote spelers in de offshore industrie.</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="footer-cta">
            <div class="lead-card surface-card">
                <h2>Klaar om de Capacity Gap te dichten?</h2>
                <p>Laat uw e-mailadres achter en start vandaag met uw compliance quick scan.</p>
                <input class="lead-input" type="email" placeholder="naam@bedrijf.sr" aria-label="Zakelijk e-mailadres">
                <button type="button" class="btn btn-primary" data-open="register">Start Mijn Quick Scan</button>
            </div>
        </section>
    </div>

    <div id="loginModal" class="modal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-head">
                <div>
                    <h3>Inloggen</h3>
                    <p>Toegang tot uw Local Content Engine dashboard.</p>
                </div>
                <button type="button" class="close" data-close>Sluit</button>
            </div>
            <form id="loginForm">
                <div class="field">
                    <label for="loginEmail">E-mail</label>
                    <input id="loginEmail" type="email" required placeholder="naam@bedrijf.sr">
                </div>
                <div class="field">
                    <label for="loginPassword">Wachtwoord</label>
                    <input id="loginPassword" type="password" required placeholder="Minimaal 8 karakters">
                </div>
                <button class="btn btn-primary" type="submit" style="width:100%;">Inloggen</button>
                <div id="loginStatus" class="status"></div>
            </form>
        </div>
    </div>

    <div id="registerModal" class="modal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-head">
                <div>
                    <h3>Registreren</h3>
                    <p>Maak uw account en start direct met uw quick scan.</p>
                </div>
                <button type="button" class="close" data-close>Sluit</button>
            </div>
            <form id="registerForm">
                <div class="field">
                    <label for="registerUsername">Gebruikersnaam (optioneel)</label>
                    <input id="registerUsername" type="text" placeholder="uw-bedrijf">
                </div>
                <div class="field">
                    <label for="registerEmail">E-mail</label>
                    <input id="registerEmail" type="email" required placeholder="naam@bedrijf.sr">
                </div>
                <div class="field">
                    <label for="registerPassword">Wachtwoord</label>
                    <input id="registerPassword" type="password" minlength="8" required placeholder="Minimaal 8 karakters">
                </div>
                <button class="btn btn-primary" type="submit" style="width:100%;">Account aanmaken</button>
                <div id="registerStatus" class="status"></div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '/api';
        const loginModal = document.getElementById('loginModal');
        const registerModal = document.getElementById('registerModal');
        const tokenKey = 'lce_token';

        function openModal(name) {
            if (name === 'login') loginModal.classList.add('open');
            if (name === 'register') registerModal.classList.add('open');
        }

        function closeModals() {
            loginModal.classList.remove('open');
            registerModal.classList.remove('open');
        }

        document.querySelectorAll('[data-open]').forEach((btn) => {
            btn.addEventListener('click', () => openModal(btn.getAttribute('data-open')));
        });
        document.querySelectorAll('[data-close]').forEach((btn) => {
            btn.addEventListener('click', closeModals);
        });
        [loginModal, registerModal].forEach((modal) => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModals();
            });
        });

        function setStatus(el, message, ok = false) {
            el.textContent = message || '';
            el.classList.toggle('ok', !!ok);
        }

        function readError(data, fallback) {
            if (!data) return fallback;
            if (typeof data === 'string') return data;
            if (data.message) return data.message;
            if (data.errors && typeof data.errors === 'object') {
                const firstKey = Object.keys(data.errors)[0];
                if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
                    return data.errors[firstKey][0];
                }
            }
            return fallback;
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const status = document.getElementById('loginStatus');
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;
            setStatus(status, 'Inloggen...');
            try {
                const res = await fetch(`${API_BASE}/auth/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.token) {
                    setStatus(status, readError(data, 'Inloggen mislukt.'));
                    return;
                }
                localStorage.setItem(tokenKey, data.token);
                setStatus(status, 'Succesvol ingelogd. Doorsturen...', true);
                window.location.href = '/dashboard';
            } catch (err) {
                setStatus(status, err.message || 'Netwerkfout tijdens inloggen.');
            }
        });

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const status = document.getElementById('registerStatus');
            const username = document.getElementById('registerUsername').value.trim();
            const email = document.getElementById('registerEmail').value.trim();
            const password = document.getElementById('registerPassword').value;
            setStatus(status, 'Registreren...');
            try {
                const payload = { email, password };
                if (username) payload.username = username;

                const res = await fetch(`${API_BASE}/auth/register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.token) {
                    setStatus(status, readError(data, 'Registratie mislukt.'));
                    return;
                }
                localStorage.setItem(tokenKey, data.token);
                setStatus(status, 'Account aangemaakt. Doorsturen...', true);
                window.location.href = '/dashboard';
            } catch (err) {
                setStatus(status, err.message || 'Netwerkfout tijdens registreren.');
            }
        });
    </script>
</body>
</html>
